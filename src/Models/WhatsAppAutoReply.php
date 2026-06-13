<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\LaravelWhatsAppCloud\Models\Concerns\BelongsToTenant;

class WhatsAppAutoReply extends Model
{
    use BelongsToTenant;
    public const TRIGGER_KEYWORD = 'keyword';

    public const TRIGGER_FIRST_MESSAGE = 'first_message';

    public const TRIGGER_ANY = 'any';

    protected $table = 'whatsapp_auto_replies';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'trigger_type',
        'trigger_value',
        'response',
        'use_ai',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'use_ai' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
