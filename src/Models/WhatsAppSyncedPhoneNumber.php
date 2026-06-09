<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppSyncedPhoneNumber extends Model
{
    protected $table = 'whatsapp_synced_phone_numbers';

    protected $fillable = [
        'account_id',
        'phone_number_id',
        'display_phone_number',
        'verified_name',
        'status',
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
