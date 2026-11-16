<?php

declare(strict_types=1);

namespace App\Secret\Actions;

use App\Secret\Properties\Id;
use App\Secret\Secret;
use App\Secret\SecretEloquentModel;

/**
 * Saves a new secret with its key/value pairs, which are encrypted on the way in.
 */
final readonly class CreateSecret
{
    /**
     * @param  list<array{key: string, value: string}>  $values
     */
    public function __construct(
        private Secret $secret,
        private array $values,
    ) {}

    public function __invoke(): Secret
    {
        if ($this->secret->getId()->isNull()) {
            $this->secret->setId(Id::generate());
        }

        $model = new SecretEloquentModel($this->secret->forDatabaseCreate());
        $model->values = $this->values;
        $model->save();

        return Secret::fromEloquentModel($model->refresh());
    }

    /**
     * @param  list<array{key: string, value: string}>  $values
     */
    public static function fromSecret(Secret $secret, array $values): Secret
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
