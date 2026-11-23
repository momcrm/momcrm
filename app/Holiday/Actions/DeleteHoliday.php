<?php

declare(strict_types=1);

namespace App\Holiday\Actions;

use App\Holiday\Holiday;
use App\Holiday\HolidayEloquentModel;

final readonly class DeleteHoliday
{
    public function __construct(
        private Holiday $holiday,
    ) {}

    public function __invoke(): void
    {
        HolidayEloquentModel::query()->withoutGlobalScopes()->find($this->holiday->getId()->toString())?->delete();
    }

    public static function fromHoliday(Holiday $holiday): void
    {
        new self($holiday)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
