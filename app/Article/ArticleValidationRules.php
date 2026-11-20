<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Properties\Body;
use App\Article\Properties\CategoryId;
use App\Article\Properties\ChangeNote;
use App\Article\Properties\Classification;
use App\Article\Properties\NextReviewOn;
use App\Article\Properties\Status;
use App\Article\Properties\Summary;
use App\Article\Properties\Title;
use App\Choice\LabelTargetEnum;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The article form's fields. Someone can only give an article a
 * classification they may read themselves.
 */
final class ArticleValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function forCreate(FormRequest $request, string $teamId, string $userId): array
    {
        return [
            Title::getNameForValidation() => Title::getValidationRulesForCreate($request),
            Classification::getNameForValidation() => self::classification($teamId, $userId),
            CategoryId::getNameForValidation() => [
                'nullable',
                'uuid',
                Rule::exists(Table::ArticleCategories->value, 'id')->where('team_id', $teamId),
            ],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function forUpdate(FormRequest $request, string $teamId, string $userId, ?string $articleId = null): array
    {
        return [
            ...self::forCreate($request, $teamId, $userId),
            Summary::getNameForValidation() => Summary::getValidationRulesForCreate($request),
            Body::getNameForValidation() => Body::getValidationRulesForCreate($request),
            Status::getNameForValidation() => ['required', Rule::enum(ArticleStatusEnum::class)],
            ChangeNote::getNameForValidation() => ChangeNote::getValidationRulesForCreate($request),
            'is_major' => ['sometimes', 'boolean'],
            NextReviewOn::getNameForValidation() => ['nullable', 'date'],
            ...LabelTargetEnum::Article->rules($teamId, $articleId),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            Classification::getNameForValidation().'.in' => __('Choose a classification you can read yourself.'),
            CategoryId::getNameForValidation().'.exists' => __('Choose one of your categories.'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function classification(string $teamId, string $userId): array
    {
        return [
            'required',
            Rule::in(array_map(
                fn (ArticleClassificationEnum $classification): string => $classification->value,
                ArticleVisibility::classificationsFor($teamId, $userId),
            )),
        ];
    }
}
