<?php

declare(strict_types=1);

namespace App\Connector\Sdk;

/**
 * Every connector type this installation knows. The app's own are registered
 * in AppServiceProvider; a package adds its own from its service provider:
 *
 *     $this->callAfterResolving(ConnectorRegistry::class, fn (ConnectorRegistry $types) => $types->register(new SlackConnector));
 */
final class ConnectorRegistry
{
    /** @var array<string, ConnectorType> */
    private array $types = [];

    public function __construct(ConnectorType ...$types)
    {
        foreach ($types as $type) {
            $this->register($type);
        }
    }

    /**
     * The registry the app is running with.
     */
    public static function instance(): self
    {
        return app(self::class);
    }

    /**
     * Adds a type, or replaces the one registered under the same key.
     */
    public function register(ConnectorType $type): void
    {
        $this->types[$type->key()] = $type;
    }

    /**
     * @return list<ConnectorType>
     */
    public function all(): array
    {
        return array_values($this->types);
    }

    public function find(?string $key): ?ConnectorType
    {
        return $key === null ? null : ($this->types[$key] ?? null);
    }

    /**
     * The type under a key, or a stand-in that can do nothing when the type
     * is no longer installed, so a connector left behind still lists.
     */
    public function get(?string $key): ConnectorType
    {
        return $this->find($key) ?? new UnknownConnectorType($key ?? '');
    }
}
