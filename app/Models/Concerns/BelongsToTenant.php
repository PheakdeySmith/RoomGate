<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Services\CurrentTenant;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (!class_exists(CurrentTenant::class)) {
                return;
            }

            $currentTenant = app(CurrentTenant::class)->get();
            if (!$currentTenant) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $builder->where("{$table}.tenant_id", $currentTenant->id);
        });
    }
}
