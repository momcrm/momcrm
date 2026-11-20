<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\Comment\CommentEloquentModel;
use App\Comment\CommentRelationship;
use App\Comment\CommentVisibilityEnum;
use App\Comment\Properties\TicketId as CommentTicketId;
use App\Comment\Properties\Visibility;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\TeamId;
use App\Ticket\Ticket;
use App\Ticket\TicketCollection;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A customer's tickets as their contacts see them in the portal: their own
 * tickets, not subtasks or archived ones, with only the public side of the
 * conversation.
 */
final class FindPortalTickets
{
    /**
     * The customer's open or closed tickets, most recently changed first.
     */
    public static function forCustomer(string $teamId, string $customerId, bool $isClosed): TicketCollection
    {
        $closedIds = self::closedStatusIds($teamId);

        return Ticket::collect(self::query($teamId, $customerId)
            ->when(
                $isClosed,
                fn (Builder $query) => $query->whereIn(StatusId::getDatabaseTableColumnName(), $closedIds),
                fn (Builder $query) => $query->whereNotIn(StatusId::getDatabaseTableColumnName(), $closedIds),
            )
            ->latest('updated_at')
            ->get()
            ->all(), 'fromEloquentModel');
    }

    public static function byIdForCustomer(?string $id, string $teamId, string $customerId): Ticket
    {
        if ($id === null) {
            return Ticket::new();
        }

        return Ticket::fromEloquentModel(self::query($teamId, $customerId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * One of the contact's customer's tickets that they may see; anything
     * else is not found, so the portal never says it exists.
     */
    public static function visibleToContact(?string $id): Ticket
    {
        $contact = PortalContact::current();
        $ticket = self::byIdForCustomer($id, $contact->getTeamId()->toString(), $contact->getCustomerId()->toString());

        if ($ticket->isNull() || ! PortalAccess::allows(AccessResourceEnum::Ticket, AccessActionEnum::View, $ticket)) {
            throw new NotFoundHttpException;
        }

        return $ticket;
    }

    /**
     * The ticket's conversation with the customer, oldest first: replies they
     * were sent and what they wrote, never staff's internal notes.
     *
     * @return list<array{id: string, body: string, author_name: ?string, is_customer: bool, created_at: ?string}>
     */
    public static function conversation(string $ticketId): array
    {
        return array_values(CommentEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(CommentRelationship::Author->value)
            ->where(CommentTicketId::getDatabaseTableColumnName(), $ticketId)
            ->where(Visibility::getDatabaseTableColumnName(), CommentVisibilityEnum::Public->value)
            ->oldest()
            ->get()
            ->map(fn (CommentEloquentModel $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author_name' => $comment->author->name ?? $comment->sender_name ?? $comment->sender_email,
                'is_customer' => $comment->user_id === null,
                'created_at' => $comment->created_at?->toIso8601String(),
            ])
            ->all());
    }

    /**
     * The team's ticket statuses by id, for showing each ticket's.
     *
     * @return array<string, array{name: string, hue: ?int, is_closed: bool}>
     */
    public static function statuses(string $teamId): array
    {
        return FindChoice::listForTeam($teamId, ChoiceListEnum::TicketStatus, withArchived: true)
            ->mapWithKeys(fn (Choice $status): array => [$status->getId()->toString() => [
                'name' => $status->getName()->toString(),
                'hue' => $status->getHue()->toNullableInteger(),
                'is_closed' => $status->hasCategory(TicketStatusCategoryEnum::Closed),
            ]])
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function closedStatusIds(string $teamId): array
    {
        return array_keys(array_filter(self::statuses($teamId), fn (array $status): bool => $status['is_closed']));
    }

    /**
     * @return Builder<TicketEloquentModel>
     */
    private static function query(string $teamId, string $customerId): Builder
    {
        return TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->whereNull(ParentId::getDatabaseTableColumnName())
            ->whereNull(ArchivedAt::getDatabaseTableColumnName());
    }
}
