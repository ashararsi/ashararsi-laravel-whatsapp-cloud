<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppScheduledMessage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $table = 'whatsapp_scheduled_messages';

    protected $fillable = [
        'account_id',
        'to',
        'type',
        'message',
        'payload_json',
        'send_at',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
