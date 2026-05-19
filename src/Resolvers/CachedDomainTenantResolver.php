<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Resolvers;

use Stancl\Tenancy\Contracts\Tenant;

/**
 * Resolver com cache worker-local. Convenção assumida: subdomínio == tenant_id.
 */
class CachedDomainTenantResolver extends DomainTenantResolver
{
    /** @var array<string|int, array> tenant_id => connection_array */
    protected static array $tenantConfigs = [];

    public function resolve(...$args): Tenant
    {
        $host = $args[0] ?? null;
        $tenantId = $this->tenantIdFromHost($host);

        if ($tenantId !== null && isset(self::$tenantConfigs[$tenantId])) {
            if ($tenant = tenancy()->find($tenantId)) {
                $this->resolved($tenant, ...$args);
                return $tenant;
            }
            unset(self::$tenantConfigs[$tenantId]);
        }

        $tenant = parent::resolve(...$args);

        if (method_exists($tenant, 'database')) {
            self::$tenantConfigs[$tenant->getTenantKey()] = $tenant->database()->connection();
        }

        return $tenant;
    }

    public static function getCachedConfig($tenantId): ?array
    {
        return self::$tenantConfigs[$tenantId] ?? null;
    }

    public static function forget($tenantId = null): void
    {
        if ($tenantId === null) {
            self::$tenantConfigs = [];
            return;
        }
        unset(self::$tenantConfigs[$tenantId]);
    }

    protected function tenantIdFromHost(?string $host): ?string
    {
        if (! $host) return null;
        $parts = explode('.', $host);
        return count($parts) < 2 ? null : $parts[0];
    }
}
