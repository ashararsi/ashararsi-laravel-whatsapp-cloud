<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Vendor\LaravelWhatsAppCloud\Models\Concerns\BelongsToTenant;

class WhatsAppContact extends Model
{
    use BelongsToTenant;
    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'tenant_id',
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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WhatsAppTag::class, 'whatsapp_contact_tag', 'contact_id', 'tag_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WhatsAppContactNote::class, 'contact_id');
    }
}
