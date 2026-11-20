<?php

declare(strict_types=1);

namespace App\Product\FormRequests;

use App\Product\Actions\CreateProduct;
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
 * Adds a product to the team's catalogue.
 */
final class StoreProductFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return RateAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ProductValidationRules::for($this, (string) $this->user()?->current_team_id);
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
        $product = CreateProduct::fromProduct(
            Product::new()
                ->setPropertyIfExists(Types::stringKeyed($this->validated()))
                ->setTeamId((string) $this->user()?->current_team_id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $product->getName()->toString()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        ProductValidationRules::prepare($this);
    }
}
