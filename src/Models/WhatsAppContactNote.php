<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppContactNote extends Model
{
    protected $table = 'whatsapp_contact_notes';

    protected $fillable = [
        'contact_id',
        'body',
        'author',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'contact_id');
    }
}
