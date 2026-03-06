<?php

namespace App\Modules\Tenant\Support;

class TenantContext
{
    private ?string $tenantId = null;

    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function tenantId(): string
    {
        if ($this->tenantId === null) {
            throw new \RuntimeException('Tenant context has not been set.');
        }

        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }
}
