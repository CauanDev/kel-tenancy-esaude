<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Bootstrappers;

use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Resolvers\CachedDomainTenantResolver;

/**
 * Variante do DatabaseTenancyBootstrapper que reusa o array de connection
 * cacheado pelo CachedDomainTenantResolver. Em miss, cai no parent.
 */
class CachedDatabaseTenancyBootstrapper extends DatabaseTenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        /** @var TenantWithDatabase $tenant */
        $cached = CachedDomainTenantResolver::getCachedConfig($tenant->getTenantKey());

        if ($cached === null) {
            parent::bootstrap($tenant);
            return;
        }

        $this->database->purgeTenantConnection();
        app('config')->set('database.connections.tenant', $cached);
        $this->database->setDefaultConnection('tenant');
    }
}
