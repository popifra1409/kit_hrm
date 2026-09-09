<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Messagerie
 */
class MessageController extends Controller
{
    private const MAX_FILE_SIZE_KB = 25 * 1024; // 25 Mo

    /**
     * Historique des messages d'une conversation (paginé, du plus récent au plus ancien)
     *
     * @queryParam before_id integer Charger les messages antérieurs à cet ID (pagination).
     */
    public function index(Request $request, int $conversationId)
    {
        $conversation = $this->findOwnConversation($request, $conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation introuvable.'], 404);
        }

        $query = $conversation->messages()->with(['sender:id,name', 'attachments'])->orderByDesc('id');

        if ($request->filled('before_id')) {
            $query->where('id', '<', $request->before_id);
        }

        $messages = $query->limit(30)->get()->sortBy('id')->values();

        $conversation->participants()->where('user_id', $request->user()->id)->update(['last_read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn($m) => $this->formatMessage($m)),
        ]);
    }

    /**
     * Envoyer un message (texte et/ou pièce jointe, 25 Mo max)
     *
     * @bodyParam body string Contenu du message (optionnel si une pièce jointe est fournie).
     * @bodyParam attachment file Pièce jointe (25 Mo max).
     *
     * @response 201 {"message_data": {"...": "..."}}
     * @response 422 scenario="Fichier trop volumineux" {"message": "Données invalides.", "errors": {"attachment": ["Le fichier ne doit pas dépasser 25 Mo."]}}
     */
    public function store(Request $request, int $conversationId)
    {
        $conversation = $this->findOwnConversation($request, $conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'body' => ['required_without:attachment', 'nullable', 'string'],
            'attachment' => ['required_without:body', 'nullable', 'file', 'max:' . self::MAX_FILE_SIZE_KB],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $request->input('body'),
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments/' . $conversation->id, 'public');

            $message->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $message->load(['sender:id,name', 'attachments']);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message_data' => $this->formatMessage($message),
        ], 201);
    }

    /**
     * Supprimer un message pour tout le monde (auteur uniquement)
     */
    public function destroy(Request $request, int $conversationId, int $messageId)
    {
        $conversation = $this->findOwnConversation($request, $conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation introuvable.'], 404);
        }

        $message = $conversation->messages()->find($messageId);

        if (!$message) {
            return response()->json(['message' => 'Message introuvable.'], 404);
        }

        if ($message->sender_id !== $request->user()->id) {
            return response()->json(['message' => "Vous ne pouvez supprimer que vos propres messages."], 403);
        }

        $message->update([
            'body' => null,
            'deleted_for_everyone_at' => now(),
        ]);

        return response()->json(['message' => 'Message supprimé.']);
    }

    private function findOwnConversation(Request $request, int $conversationId): ?Conversation
    {
        return Conversation::where('id', $conversationId)
            ->whereHas('participants', fn($q) => $q->where('user_id', $request->user()->id)->whereNull('left_at'))
            ->first();
    }

    private function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name,
            'body' => $message->isDeleted() ? null : $message->body,
            'is_deleted' => $message->isDeleted(),
            'attachments' => $message->attachments->map(fn($a) => [
                'id' => $a->id,
                'file_name' => $a->file_name,
                'url' => $a->url,
                'human_size' => $a->human_size,
                'mime_type' => $a->mime_type,
            ]),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
