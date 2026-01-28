<?php

namespace Modules\Core\App\Services;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CurrentTenant
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function getOrFail(): Tenant
    {
        if (! $this->tenant) {
            throw (new ModelNotFoundException())->setModel(Tenant::class);
        }

        return $this->tenant;
    }
}
