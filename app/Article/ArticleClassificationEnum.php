<?php

declare(strict_types=1);

namespace App\Article;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use App\TeamUser\TeamRoleEnum;

/**
 * Who in the team may read an article, by their role. Each tier is read by
 * the roles of the tiers above it too, so owners read everything.
 */
enum ArticleClassificationEnum: string
{
    use Enum;
    use EnumOptions;

    case Internal = 'internal';
    case Confidential = 'confidential';
    case StrictlyConfidential = 'strictly_confidential';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::Confidential => 'Confidential',
            self::StrictlyConfidential => 'Strictly confidential',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Internal => 'Everyone in the team',
            self::Confidential => 'Owners and admins',
            self::StrictlyConfidential => 'Owners only',
        };
    }

    public function isReadableBy(TeamRoleEnum $role): bool
    {
        return match ($this) {
            self::Internal => true,
            self::Confidential => $role !== TeamRoleEnum::Member,
            self::StrictlyConfidential => $role === TeamRoleEnum::Owner,
        };
    }

    /**
     * The tiers someone in this role may read, and so may give an article.
     *
     * @return list<self>
     */
    public static function readableBy(?TeamRoleEnum $role): array
    {
        if ($role === null) {
            return [];
        }

        return array_values(array_filter(self::cases(), fn (self $classification): bool => $classification->isReadableBy($role)));
    }

    /**
     * Options for the tiers someone in this role may choose.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function optionsFor(?TeamRoleEnum $role): array
    {
        return array_map(
            fn (self $classification): array => ['value' => $classification->value, 'label' => $classification->label(), 'description' => $classification->description()],
            self::readableBy($role),
        );
    }
}
