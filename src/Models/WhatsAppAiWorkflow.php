<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppAiWorkflow extends Model
{
    protected $table = 'whatsapp_ai_workflows';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'description',
        'system_prompt',
        'steps_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
