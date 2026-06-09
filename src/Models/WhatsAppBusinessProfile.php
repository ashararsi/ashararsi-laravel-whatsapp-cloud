<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBusinessProfile extends Model
{
    protected $table = 'whatsapp_business_profiles';

    protected $fillable = [
        'account_id',
        'business_name',
        'display_name',
        'verification_status',
        'quality_rating',
        'messaging_tier',
        'meta_json',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
