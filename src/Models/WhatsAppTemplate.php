<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property string $provider
 * @property string $template_name
 * @property string|null $category
 * @property string $language
 * @property string|null $status
 * @property array<int, mixed>|null $components_json
 * @property string|null $meta_template_id
 * @property Carbon|null $synced_at
 * @property-read WhatsAppAccount|null $account
 */
class WhatsAppTemplate extends Model
{
    public const CATEGORY_UTILITY = 'UTILITY';

    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';

    public const CATEGORY_MARKETING = 'MARKETING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_DISABLED = 'DISABLED';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
        self::STATUS_DISABLED,
    ];

    /**
     * @var list<string>
     */
    public const CATEGORIES = [
        self::CATEGORY_UTILITY,
        self::CATEGORY_AUTHENTICATION,
        self::CATEGORY_MARKETING,
    ];

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'account_id',
        'provider',
        'template_name',
        'category',
        'language',
        'status',
        'components_json',
        'meta_template_id',
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

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! is_string($term) || trim($term) === '') {
            return $query;
        }

        $search = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($search) {
            $q->where('template_name', 'like', $search)
                ->orWhere('language', 'like', $search)
                ->orWhere('status', 'like', $search);
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if (! is_string($category) || trim($category) === '') {
            return $query;
        }

        return $query->where('category', strtoupper(trim($category)));
    }

    public function isApproved(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_APPROVED;
    }

    public function statusBadgeClass(): string
    {
        return match (strtoupper((string) $this->status)) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_DISABLED => 'secondary',
            default => 'secondary',
        };
    }
}
