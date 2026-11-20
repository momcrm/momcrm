<?php

declare(strict_types=1);

namespace App\Product\Actions;

use App\Product\Product;
use Carbon\CarbonImmutable;

/**
 * Archives a product, or brings it back. An archived product is no longer
 * offered for new charges; charges already made from it keep their price.
 */
final readonly class ArchiveProduct
{
    public function __construct(
        private Product $product,
        private bool $archive = true,
    ) {}

    public function __invoke(): Product
    {
        return UpdateProduct::fromProduct($this->product->setArchivedAt($this->archive ? CarbonImmutable::now() : null));
    }

    public static function archive(Product $product): Product
    {
        return new self($product, archive: true)->handle();
    }

    public static function restore(Product $product): Product
    {
        return new self($product, archive: false)->handle();
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
