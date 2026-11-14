<?php

declare(strict_types=1);

namespace App\Asset\Actions;

use App\Asset\Asset;
use App\Asset\AssetEloquentModel;
use App\Asset\Properties\Id;
use Illuminate\Support\Facades\DB;

final readonly class CreateAsset
{
    public function __construct(
        private Asset $asset,
    ) {}

    public function __invoke(): Asset
    {
        return DB::transaction(function (): Asset {
            if ($this->asset->getId()->isNull()) {
                $this->asset->setId(Id::generate());
            }

            $model = AssetEloquentModel::query()->create($this->asset->forDatabaseCreate());

            return Asset::fromEloquentModel($model->refresh());
        });
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
