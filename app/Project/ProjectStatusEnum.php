<?php

declare(strict_types=1);

namespace App\Project;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum ProjectStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case AtRisk = 'at_risk';
    case Completed = 'completed';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Planning = 'planning';

    public function label(): string
    {
        return match ($this) {
            self::AtRisk => 'At risk',
            self::Completed => 'Completed',
            self::InProgress => 'In progress',
            self::OnHold => 'On hold',
            self::Planning => 'Planning',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Completed;
    }
}
