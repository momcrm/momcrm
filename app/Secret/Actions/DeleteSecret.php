<?php

declare(strict_types=1);

namespace App\Secret\Actions;

use App\Secret\Secret;
use App\Secret\SecretEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteSecret
{
    public function __construct(
        private Secret $secret,
    ) {}

    public function __invoke(): void
    {
        SecretEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->secret->getId()->toString())
            ?->delete();
    }

    public static function fromSecret(Secret $secret): void
    {
        new self($secret)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
