<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Archives a choice, or brings it back. An archived choice is no longer
 * offered, but records that use it keep it, unless they are moved to a
 * replacement while it is archived.
 */
final readonly class ArchiveChoice
{
    public function __construct(
        private Choice $choice,
        private bool $archive = true,
        private ?string $replacementId = null,
    ) {}

    public function __invoke(): Choice
    {
        return DB::transaction(function (): Choice {
            if ($this->archive && $this->replacementId !== null) {
                $id = $this->choice->getId()->toString();

                foreach ($this->choice->getListName()->toEnum()->usages() as $usage) {
                    DB::table($usage['table']->value)
                        ->where('team_id', $this->choice->getTeamId()->toString())
                        ->where($usage['column'], $id)
                        ->update([$usage['column'] => $this->replacementId]);
                }
            }

            return UpdateChoice::fromChoice($this->choice->setArchivedAt($this->archive ? CarbonImmutable::now() : null));
        });
    }

    public static function archive(Choice $choice, ?string $replacementId = null): Choice
    {
        return new self($choice, archive: true, replacementId: $replacementId)->handle();
    }

    public static function restore(Choice $choice): Choice
    {
        return new self($choice, archive: false)->handle();
    }

    public function handle(): Choice
    {
        return $this->__invoke();
    }

    public function execute(): Choice
    {
        return $this->__invoke();
    }
}
