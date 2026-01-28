<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Services\CurrentTenant;

trait BelongsToTenantOrGlobal
{
    protected static function bootBelongsToTenantOrGlobal(): void
    {
        static::addGlobalScope('tenant_or_global', function (Builder $builder) {
            if (!class_exists(CurrentTenant::class)) {
                return;
            }

            $currentTenant = app(CurrentTenant::class)->get();
            if (!$currentTenant) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $builder->where(function (Builder $query) use ($table, $currentTenant) {
                $query->whereNull("{$table}.tenant_id")
                    ->orWhere("{$table}.tenant_id", $currentTenant->id);
            });
        });
    }
}
