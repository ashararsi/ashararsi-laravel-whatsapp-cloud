<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMediaFile extends Model
{
    protected $table = 'whatsapp_media_files';

    protected $fillable = [
        'account_id',
        'conversation_message_id',
        'media_id',
        'mime_type',
        'disk',
        'path',
        'url',
        'size',
        'transcription',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function conversationMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversationMessage::class, 'conversation_message_id');
    }
}
