<?php

declare(strict_types=1);

namespace App\Secret\Actions;

use App\Secret\Secret;
use App\Secret\SecretEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Saves a secret's title, description and asset, and its key/value pairs when
 * new ones are given. Changed pairs raise the version, which is what the audit
 * log shows of them.
 */
final readonly class UpdateSecret
{
    /**
     * @param  list<array{key: string, value: string}>|null  $values  null leaves them as they are
     */
    public function __construct(
        private Secret $secret,
        private ?array $values = null,
    ) {}

    public function __invoke(): Secret
    {
        $model = SecretEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->secret->getId()->toString());

        if ($model === null) {
            return $this->secret;
        }

        $model->fill($this->secret->forDatabaseUpdate());

        if ($this->values !== null && $this->values !== $model->values) {
            $model->values = $this->values;
            $model->version++;
        }

        $model->save();

        return Secret::fromEloquentModel($model->refresh());
    }

    /**
     * @param  list<array{key: string, value: string}>|null  $values
     */
    public static function fromSecret(Secret $secret, ?array $values = null): Secret
    {
        return new self($secret, $values)->handle();
    }

    public function handle(): Secret
    {
        return $this->__invoke();
    }

    public function execute(): Secret
    {
        return $this->__invoke();
    }
}
