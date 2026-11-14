<?php

declare(strict_types=1);

namespace App\Asset\Actions;

use App\Asset\Asset;
use App\Asset\AssetEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateAsset
{
    public function __construct(
        private Asset $asset,
    ) {}

    public function __invoke(): Asset
    {
        AssetEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->asset->getId()->toString())
            ?->update($this->asset->forDatabaseUpdate());

        return $this->asset;
    }

    public static function fromAsset(Asset $asset): Asset
    {
        return new self($asset)->handle();
    }

    public function handle(): Asset
    {
        return $this->__invoke();
    }

    public function execute(): Asset
    {
        return $this->__invoke();
    }
}
