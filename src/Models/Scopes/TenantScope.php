<?php

namespace Vendor\LaravelWhatsAppCloud\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->shouldScope()) {
            return;
        }

        $builder->where(
            $model->getTable().'.'.$context->column(),
            $context->id(),
        );
    }
}
