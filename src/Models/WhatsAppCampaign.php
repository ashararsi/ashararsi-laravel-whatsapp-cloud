<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'whatsapp_campaigns';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'type',
        'message',
        'payload_json',
        'status',
        'scheduled_at',
        'sent_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'campaign_id');
    }
}
