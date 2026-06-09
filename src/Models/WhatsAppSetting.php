<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $group
 * @property string $type
 * @property string $value
 */
class WhatsAppSetting extends Model
{
    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'float';

    public const TYPE_STRING = 'string';

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'key',
        'group',
        'type',
        'value',
    ];
}
