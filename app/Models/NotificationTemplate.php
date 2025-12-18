<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'email_enabled',
        'sms_enabled',
        'whatsapp_enabled',
        'system_enabled',
        'email_subject',
        'email_body',
        'sms_body',
        'whatsapp_body',
        'system_title',
        'system_body',
        'system_icon',
        'system_color',
        'available_variables',
        'is_active',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'system_enabled' => 'boolean',
        'is_active' => 'boolean',
        'available_variables' => 'array',
    ];

    // Remplacer les variables dans un texte
    public function replaceVariables($text, $variables)
    {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{" . $key . "}}", $value, $text);
        }
        return $text;
    }

    // Générer les contenus avec variables
    public function generateContent($variables)
    {
        return [
            'email' => [
                'subject' => $this->replaceVariables($this->email_subject, $variables),
                'body' => $this->replaceVariables($this->email_body, $variables),
            ],
            'sms' => [
                'body' => $this->replaceVariables($this->sms_body, $variables),
            ],
            'whatsapp' => [
                'body' => $this->replaceVariables($this->whatsapp_body, $variables),
            ],
            'system' => [
                'title' => $this->replaceVariables($this->system_title, $variables),
                'body' => $this->replaceVariables($this->system_body, $variables),
            ],
        ];
    }
}
