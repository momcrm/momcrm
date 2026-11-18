<?php

declare(strict_types=1);

namespace App\Connector\Sdk;

use App\Connector\ConnectorCapabilityEnum;
use ReflectionClass;

/**
 * A kind of outside account a team can connect. To add one, extend this in a
 * folder of its own under app/Connector/Types and register it with the
 * ConnectorRegistry. Nothing else in the app needs to change.
 *
 * A type says how an account is connected, by implementing ConnectsBySignIn
 * or ConnectsByForm, and what a connected account can be used for, by
 * implementing each capability's contract (ProvidesMail for email).
 */
abstract class ConnectorType
{
    /**
     * What a connector of this type is stored as. Never change it once
     * accounts are connected.
     */
    abstract public function key(): string;

    /**
     * What people see the type as, such as "Microsoft 365".
     */
    abstract public function label(): string;

    /**
     * What it connects, in a line, for the page that offers it.
     */
    abstract public function description(): string;

    /**
     * Who runs the service, as error messages name it.
     */
    public function company(): string
    {
        return $this->label();
    }

    /**
     * Whether this installation is set up to connect accounts of this type.
     * A sign-in type needs its keys; anything else is ready as it is.
     */
    public function isAvailable(): bool
    {
        if (! $this instanceof ConnectsBySignIn) {
            return true;
        }

        $driver = $this->signInDriver();

        return filled(config("services.{$driver}.client_id")) && filled(config("services.{$driver}.client_secret"));
    }

    /**
     * The type's logo as an image address the page can show as it is. By
     * default this is the logo.svg file beside the class; none means the
     * page shows a plain icon.
     */
    public function logo(): ?string
    {
        $path = dirname((string) new ReflectionClass($this)->getFileName()).'/logo.svg';
        $svg = is_file($path) ? file_get_contents($path) : false;

        return $svg === false ? null : 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * What a connected account of this type can be used for: every
     * capability whose contract the type implements.
     *
     * @return list<ConnectorCapabilityEnum>
     */
    final public function capabilities(): array
    {
        return array_values(array_filter(
            ConnectorCapabilityEnum::cases(),
            fn (ConnectorCapabilityEnum $capability): bool => $this->provides($capability),
        ));
    }

    final public function provides(ConnectorCapabilityEnum $capability): bool
    {
        return is_a($this, $capability->contract());
    }

    /**
     * The type for the page that offers it: what it is, and what the page
     * needs to connect one, which is a sign-in's options or a form's fields.
     *
     * @return array{value: string, label: string, description: string, logo: ?string, capabilities: list<string>, available: bool, connection: 'form'|'sign_in'|null, options: list<array{key: string, label: string, hint: ?string}>, fields: list<array<string, mixed>>, account_field: ?string, form_introduction: ?string}
     */
    final public function toCatalogItem(): array
    {
        return [
            'value' => $this->key(),
            'label' => $this->label(),
            'description' => $this->description(),
            'logo' => $this->logo(),
            'capabilities' => array_map(fn (ConnectorCapabilityEnum $capability): string => $capability->label(), $this->capabilities()),
            'available' => $this->isAvailable(),
            'connection' => match (true) {
                $this instanceof ConnectsBySignIn => 'sign_in',
                $this instanceof ConnectsByForm => 'form',
                default => null,
            },
            'options' => $this instanceof ConnectsBySignIn
                ? array_map(fn (SignInOption $option): array => $option->toArray(), $this->signInOptions())
                : [],
            'fields' => $this instanceof ConnectsByForm
                ? array_map(fn (Field $field): array => $field->toArray(), $this->fields())
                : [],
            'account_field' => $this instanceof ConnectsByForm ? $this->accountField() : null,
            'form_introduction' => $this instanceof ConnectsByForm ? $this->formIntroduction() : null,
        ];
    }
}
