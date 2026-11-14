<?php

declare(strict_types=1);

namespace App\Asset\FormRequests;

use App\Asset\Actions\DeleteAsset;
use App\Asset\Actions\FindAsset;
use App\Asset\Asset;
use App\Asset\Exceptions\CouldNotFindAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class DeleteAssetFormRequest extends FormRequest
{
    public Asset $asset;

    public function __construct()
    {
        parent::__construct();

        $this->asset = Asset::new();
    }

    /**
     * @throws CouldNotFindAsset
     */
    public function authorize(): bool
    {
        $this->asset = FindAsset::byIdForTeam((string) $this->route('asset'), $this->user()?->current_team_id);

        if ($this->asset->isNull()) {
            throw CouldNotFindAsset::notFound();
        }

        return true;
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
        DeleteAsset::fromAsset($this->asset);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->asset->getName()->toString()])]);

        return to_route('customers.show', $this->asset->getCustomerId()->toString());
    }
}
