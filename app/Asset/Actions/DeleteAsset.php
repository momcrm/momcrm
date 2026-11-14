<?php

declare(strict_types=1);

namespace App\Asset\Actions;

use App\Asset\Asset;
use App\Asset\AssetEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteAsset
{
    public function __construct(
        private Asset $asset,
    ) {}

    public function __invoke(): void
    {
        AssetEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->asset->getId()->toString())
            ?->delete();
    }

    public static function fromAsset(Asset $asset): void
    {
        new self($asset)->handle();
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
