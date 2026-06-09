<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    protected $fillable = [
        'account_id',
        'whatsapp_message_id',
        'to',
        'type',
        'message',
        'status',
        'meta_json',
        'response_json',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'response_json' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
