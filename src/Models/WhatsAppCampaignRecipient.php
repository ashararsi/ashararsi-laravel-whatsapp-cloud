<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppCampaignRecipient extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $table = 'whatsapp_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'phone',
        'status',
        'sent_at',
        'response_json',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'response_json' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'contact_id');
    }
}
