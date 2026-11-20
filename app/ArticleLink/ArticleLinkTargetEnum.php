<?php

declare(strict_types=1);

namespace App\ArticleLink;

use App\Asset\Actions\FindAsset;
use App\Asset\AssetEloquentModel;
use App\Service\Actions\FindService;
use App\Service\ServiceEloquentModel;
use App\Shared\Traits\Enum;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Actions\FindTicket;
use App\Ticket\TicketEloquentModel;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractData;

/**
 * The kinds of record an article can be attached to. Tickets are read
 * through their visibility scope, so a ticket in a private project is only
 * found by the people who can see it.
 */
enum ArticleLinkTargetEnum: string
{
    use Enum;

    case Ticket = 'ticket';
    case Asset = 'asset';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Ticket => 'Tickets',
            self::Asset => 'Assets',
            self::Service => 'Services',
        };
    }

    /**
     * One of the team's records of this kind, or an empty one.
     *
     * @return AbstractData<covariant Model>
     */
    public function find(string $id, string $teamId): AbstractData
    {
        return match ($this) {
            self::Ticket => FindTicket::byIdForTeam($id, $teamId),
            self::Asset => FindAsset::byIdForTeam($id, $teamId),
            self::Service => FindService::byIdForTeam($id, $teamId),
        };
    }

    /**
     * What the records are called and where their pages are, by id, for the
     * ones the signed-in person can see.
     *
     * @param  list<string>  $ids
     * @return array<string, array{title: string, url: string}>
     */
    public function describe(array $ids, string $teamId): array
    {
        if ($ids === []) {
            return [];
        }

        return match ($this) {
            self::Ticket => self::described(
                TicketEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where('team_id', $teamId)->whereKey($ids)->get()->all(),
                fn (TicketEloquentModel $ticket): array => ['title' => "#{$ticket->number} {$ticket->title}", 'url' => route('tickets.show', $ticket->id)],
            ),
            self::Asset => self::described(
                AssetEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where('team_id', $teamId)->whereKey($ids)->get()->all(),
                fn (AssetEloquentModel $asset): array => ['title' => $asset->name, 'url' => route('assets.edit', $asset->id)],
            ),
            self::Service => self::described(
                ServiceEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where('team_id', $teamId)->whereKey($ids)->get()->all(),
                fn (ServiceEloquentModel $service): array => ['title' => $service->name, 'url' => route('services.show', $service->id)],
            ),
        };
    }

    /**
     * @template TModel of Model
     *
     * @param  array<int, TModel>  $records
     * @param  callable(TModel): array{title: string, url: string}  $describe
     * @return array<string, array{title: string, url: string}>
     */
    private static function described(array $records, callable $describe): array
    {
        $described = [];

        foreach ($records as $record) {
            $described[Types::string($record->getKey())] = $describe($record);
        }

        return $described;
    }
}
