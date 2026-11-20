<?php

declare(strict_types=1);

namespace App\Product\FormRequests;

use App\Product\Actions\ArchiveProduct;
use App\Product\Actions\FindProduct;
use App\Product\Exceptions\CouldNotFindProduct;
use App\Product\Product;
use App\Rate\RateAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Archives a product so it is no longer offered for new charges.
 */
final class ArchiveProductFormRequest extends FormRequest
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

        if ($this->product->isNull() || $this->product->isArchived()) {
            throw CouldNotFindProduct::notFound();
        }

        return RateAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        ArchiveProduct::archive($this->product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name archived.', ['name' => $this->product->getName()->toString()])]);

        return back();
    }
}
