<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'account_id',
        'name',
        'language',
        'category',
        'status',
        'components_json',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'components_json' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
