<?php

declare(strict_types=1);

namespace App\Product\Actions;

use App\Product\Product;
use App\Product\ProductEloquentModel;
use App\Product\Properties\Id;

final readonly class CreateProduct
{
    public function __construct(
        private Product $product,
    ) {}

    public function __invoke(): Product
    {
        if ($this->product->getId()->isNull()) {
            $this->product->setId(Id::generate());
        }

        $model = ProductEloquentModel::query()->create($this->product->forDatabaseCreate());

        return Product::fromEloquentModel($model->refresh());
    }

    public static function fromProduct(Product $product): Product
    {
        return new self($product)->handle();
    }

    public function handle(): Product
    {
        return $this->__invoke();
    }

    public function execute(): Product
    {
        return $this->__invoke();
    }
}
