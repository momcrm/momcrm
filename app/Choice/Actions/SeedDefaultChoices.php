<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceListEnum;

/**
 * Gives a new team the starting choices of every list (ChoiceListEnum::defaults()).
 */
final readonly class SeedDefaultChoices
{
    public function __construct(
        private string $teamId,
    ) {}

    public function __invoke(): void
    {
        foreach (ChoiceListEnum::cases() as $list) {
            foreach ($list->defaults() as $position => $default) {
                CreateChoice::fromChoice(
                    Choice::new()
                        ->setTeamId($this->teamId)
                        ->setListName($list)
                        ->setName($default['name'])
                        ->setColor($default['color'])
                        ->setIcon($default['icon'])
                        ->setCategory($default['category'])
                        ->setPosition($position)
                        ->setIsDefault($default['is_default']),
                );
            }
        }
    }

    public static function forTeam(string $teamId): void
    {
        new self($teamId)->handle();
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
