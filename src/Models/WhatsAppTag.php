<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Vendor\LaravelWhatsAppCloud\Models\Concerns\BelongsToTenant;

class WhatsAppTag extends Model
{
    use BelongsToTenant;
    protected $table = 'whatsapp_tags';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'color',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(WhatsAppContact::class, 'whatsapp_contact_tag', 'tag_id', 'contact_id');
    }
}
