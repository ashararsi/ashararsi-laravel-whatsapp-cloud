<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppTenant extends Model
{
    protected $table = 'whatsapp_tenants';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings_json' => 'array',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(WhatsAppAccount::class, 'tenant_id');
    }
}
