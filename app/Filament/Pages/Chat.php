<?php

namespace App\Filament\Pages;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

class Chat extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.chat';
    protected static ?string $navigationGroup = '👥 Gestion du Personnel';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Messagerie';
    }

    public function getTitle(): string
    {
        return 'Messagerie Interne';
    }

    public int $selectedConversationId = 0;
    public string $newMessageBody = '';
    public $newAttachment = null;

    public ?int $newDirectUserId = null;

    public bool $showGroupModal = false;
    public string $newGroupName = '';
    public array $newGroupUserIds = [];

    public function mount(): void
    {
        //
    }

    public function getConversations()
    {
        $userId = auth()->id();

        return Conversation::whereHas('participants', fn($q) => $q->where('user_id', $userId)->whereNull('left_at'))
            ->with(['users:id,name', 'latestMessage.sender:id,name'])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($c) use ($userId) {
                return [
                    'id' => $c->id,
                    'name' => $c->isGroup() ? $c->name : $c->users->firstWhere('id', '!=', $userId)?->name,
                    'is_group' => $c->isGroup(),
                    'last_message' => $c->latestMessage?->body ?? ($c->latestMessage ? '📎 Pièce jointe' : null),
                    'last_message_at' => $c->last_message_at,
                    'unread_count' => $c->unreadCountFor($userId),
                ];
            });
    }

    public function getMessages()
    {
        if (!$this->selectedConversationId) {
            return collect();
        }

        $conversation = $this->currentConversation();

        if (!$conversation) {
            return collect();
        }

        return $conversation->messages()->with(['sender:id,name', 'attachments'])->get();
    }

    public function getAvailableUsers()
    {
        return User::where('id', '!=', auth()->id())->orderBy('name')->get(['id', 'name']);
    }

    protected function currentConversation(): ?Conversation
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        return Conversation::where('id', $this->selectedConversationId)
            ->whereHas('participants', fn($q) => $q->where('user_id', auth()->id())->whereNull('left_at'))
            ->first();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;

        $conversation = $this->currentConversation();

        if ($conversation) {
            $conversation->participants()->where('user_id', auth()->id())->update(['last_read_at' => now()]);
        }
    }

    public function sendMessage(): void
    {
        $conversation = $this->currentConversation();

        if (!$conversation) {
            return;
        }

        if (trim($this->newMessageBody) === '' && !$this->newAttachment) {
            return;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => trim($this->newMessageBody) !== '' ? $this->newMessageBody : null,
        ]);

        if ($this->newAttachment) {
            if ($this->newAttachment->getSize() > 25 * 1024 * 1024) {
                Notification::make()->title('Fichier trop volumineux (25 Mo max)')->danger()->send();
                $message->delete();
                return;
            }

            $path = $this->newAttachment->store('chat-attachments/' . $conversation->id, 'public');

            $message->attachments()->create([
                'file_path' => $path,
                'file_name' => $this->newAttachment->getClientOriginalName(),
                'mime_type' => $this->newAttachment->getMimeType(),
                'file_size' => $this->newAttachment->getSize(),
            ]);
        }

        $message->load(['sender:id,name', 'attachments']);

        broadcast(new MessageSent($message))->toOthers();

        $this->newMessageBody = '';
        $this->newAttachment = null;
    }

    #[On('echo-private:conversation.{selectedConversationId},message.sent')]
    public function onMessageReceived(): void
    {
        if ($this->selectedConversationId) {
            $this->currentConversation()?->participants()
                ->where('user_id', auth()->id())
                ->update(['last_read_at' => now()]);
        }
    }

    public function startDirectConversation(): void
    {
        if (!$this->newDirectUserId) {
            return;
        }

        $existing = Conversation::findDirectBetween(auth()->id(), $this->newDirectUserId);

        if ($existing) {
            $this->selectedConversationId = $existing->id;
            $this->newDirectUserId = null;
            return;
        }

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'created_by' => auth()->id(),
            'last_message_at' => now(),
        ]);

        $conversation->participants()->createMany([
            ['user_id' => auth()->id(), 'role' => 'member', 'joined_at' => now()],
            ['user_id' => $this->newDirectUserId, 'role' => 'member', 'joined_at' => now()],
        ]);

        $this->selectedConversationId = $conversation->id;
        $this->newDirectUserId = null;
    }

    public function openGroupModal(): void
    {
        $this->showGroupModal = true;
        $this->newGroupName = '';
        $this->newGroupUserIds = [];
    }

    public function createGroup(): void
    {
        if (trim($this->newGroupName) === '' || empty($this->newGroupUserIds)) {
            Notification::make()->title('Nom et au moins un membre requis')->warning()->send();
            return;
        }

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'name' => $this->newGroupName,
            'created_by' => auth()->id(),
            'last_message_at' => now(),
        ]);

        $conversation->participants()->create([
            'user_id' => auth()->id(),
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        foreach ($this->newGroupUserIds as $userId) {
            $conversation->participants()->create([
                'user_id' => $userId,
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }

        $this->showGroupModal = false;
        $this->selectedConversationId = $conversation->id;

        Notification::make()->title('Groupe créé')->success()->send();
    }
}
