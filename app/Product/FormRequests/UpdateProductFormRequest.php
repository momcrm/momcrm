<?php

declare(strict_types=1);

namespace App\Product\FormRequests;

use App\Product\Actions\FindProduct;
use App\Product\Actions\UpdateProduct;
use App\Product\Exceptions\CouldNotFindProduct;
use App\Product\Product;
use App\Product\ProductValidationRules;
use App\Rate\RateAccess;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Changes a product's name, description or prices.
 */
final class UpdateProductFormRequest extends FormRequest
{
    public Product $product;

    public function __construct()
    {
        parent::__construct();

        $this->product = Product::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindProduct
     */
    public function authorize(): Response
    {
        $this->product = FindProduct::byIdForTeam((string) $this->route('product'), RateAccess::team($this)->getId()->toString());

        if ($this->product->isNull()) {
            throw CouldNotFindProduct::notFound();
        }

        return RateAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ProductValidationRules::for($this, $this->product->getTeamId()->toString(), $this->product);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ProductValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $product = UpdateProduct::fromProduct($this->product->setPropertyIfExists(Types::stringKeyed($this->validated())));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $product->getName()->toString()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        ProductValidationRules::prepare($this);
    }
}
