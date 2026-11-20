<?php

declare(strict_types=1);

namespace App\Product\Actions;

use App\Product\Product;
use App\Product\ProductEloquentModel;

/**
 * Saves a product's name, description or prices. Charges already made
 * from it keep the price they were made at.
 */
final readonly class UpdateProduct
{
    public function __construct(
        private Product $product,
    ) {}

    public function __invoke(): Product
    {
        $model = ProductEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->product->getId()->toString());
        $model->update($this->product->forDatabaseUpdate());

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
