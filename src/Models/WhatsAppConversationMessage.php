<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConversationMessage extends Model
{
    public const DIRECTION_INCOMING = 'incoming';

    public const DIRECTION_OUTGOING = 'outgoing';

    protected $table = 'whatsapp_conversation_messages';

    protected $fillable = [
        'conversation_id',
        'direction',
        'whatsapp_message_id',
        'phone',
        'message',
        'type',
        'payload_json',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function isIncoming(): bool
    {
        return $this->direction === self::DIRECTION_INCOMING;
    }

    public function isOutgoing(): bool
    {
        return $this->direction === self::DIRECTION_OUTGOING;
    }
}
