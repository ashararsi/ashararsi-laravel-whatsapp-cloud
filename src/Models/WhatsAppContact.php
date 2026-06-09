<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppContact extends Model
{
    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'account_id',
        'phone',
        'name',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(WhatsAppConversation::class, 'contact_id');
    }
}
