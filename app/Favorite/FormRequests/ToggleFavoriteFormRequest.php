<?php

declare(strict_types=1);

namespace App\Favorite\FormRequests;

use App\Favorite\Actions\ToggleFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

final class ToggleFavoriteFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->current_team_id !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = FavoritableTypeEnum::tryFrom((string) $this->input(FavoritableType::getNameForValidation()));

        return [
            FavoritableType::getNameForValidation() => ['required', Rule::enum(FavoritableTypeEnum::class)],
            FavoritableId::getNameForValidation() => [
                'required',
                'uuid',
                ...($type ? [Rule::exists($type->table()->value, 'id')->where('team_id', $this->user()?->current_team_id)] : []),
            ],
        ];
    }

    public function process(): RedirectResponse
    {
        ToggleFavorite::for(
            teamId: (string) $this->user()?->current_team_id,
            userId: (string) $this->user()?->getAuthIdentifier(),
            type: FavoritableTypeEnum::from($this->string(FavoritableType::getNameForValidation())->toString()),
            favoritableId: $this->string(FavoritableId::getNameForValidation())->toString(),
        );

        return back();
    }
}
