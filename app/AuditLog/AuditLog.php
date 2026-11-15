<?php

declare(strict_types=1);

namespace App\AuditLog;

use App\AuditLog\Properties\Actor;
use App\AuditLog\Properties\AuditableId;
use App\AuditLog\Properties\AuditableType;
use App\AuditLog\Properties\CreatedAt;
use App\AuditLog\Properties\Event;
use App\AuditLog\Properties\Id;
use App\AuditLog\Properties\IpAddress;
use App\AuditLog\Properties\NewValues;
use App\AuditLog\Properties\OldValues;
use App\AuditLog\Properties\RequestId;
use App\AuditLog\Properties\TeamId;
use App\AuditLog\Properties\Url;
use App\AuditLog\Properties\UserAgent;
use App\AuditLog\Properties\UserId;
use App\Shared\Abstracts\AbstractUuid;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractString;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<AuditLogEloquentModel>
 */
final class AuditLog extends AbstractData
{
    public function __construct(
        public Actor $actor,
        public AuditableId $auditableId,
        public AuditableType $auditableType,
        public CreatedAt $createdAt,
        public Event $event,
        public Id $id,
        public IpAddress $ipAddress,
        public NewValues $newValues,
        public OldValues $oldValues,
        public RequestId $requestId,
        public TeamId $teamId,
        public Url $url,
        public UserAgent $userAgent,
        public UserId $userId,
    ) {}

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): AuditLogCollection
    {
        if ($items instanceof AuditLogCollection) {
            return $items;
        }

        return AuditLogCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The row as shown in audit trails, with one entry per changed field.
     *
     * @return array{id: string, event: string, subject: string, changes: array<int, array{field: string, old: string|null, new: string|null}>, actor: string|null, ip_address: string|null, created_at: string|null}
     */
    public function toListItem(): array
    {
        $old = $this->getOldValues()->toNullableArray() ?? [];
        $new = $this->getNewValues()->toNullableArray() ?? [];

        $changes = collect(array_unique([...array_keys($old), ...array_keys($new)]))
            ->map(fn (int|string $field): array => [
                'field' => (string) $field,
                'old' => self::displayValue($old[$field] ?? null),
                'new' => self::displayValue($new[$field] ?? null),
            ])
            ->values()
            ->all();

        return [
            'id' => $this->getId()->toString(),
            'event' => $this->getEvent()->toString(),
            'subject' => $this->getAuditableType()->toString(),
            'changes' => $changes,
            'actor' => $this->getActor()->toNullableData()?->getName()->toString(),
            'ip_address' => $this->getIpAddress()->toNullableString(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
        ];
    }

    private static function displayValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }

    public function getAuditableId(): AuditableId
    {
        return $this->auditableId;
    }

    public function setAuditableId(AuditableId|AbstractUuid|string|null $auditableId): self
    {
        if ($auditableId instanceof AuditableId) {
            $this->auditableId = $auditableId;

            return $this;
        }

        if ($auditableId instanceof AbstractUuid) {
            $this->auditableId = AuditableId::fromNullableUuid($auditableId->toNullableString());

            return $this;
        }

        $this->auditableId = AuditableId::fromNullableUuid($auditableId);

        return $this;
    }

    public function getAuditableType(): AuditableType
    {
        return $this->auditableType;
    }

    public function setAuditableType(AuditableType|MorphMapKeyEnum|string $auditableType): self
    {
        if ($auditableType instanceof AuditableType) {
            $this->auditableType = $auditableType;

            return $this;
        }

        if ($auditableType instanceof MorphMapKeyEnum) {
            $this->auditableType = AuditableType::fromEnum($auditableType);

            return $this;
        }

        $this->auditableType = AuditableType::fromNullableString($auditableType);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event|AuditEventEnum|string $event): self
    {
        if ($event instanceof Event) {
            $this->event = $event;

            return $this;
        }

        if ($event instanceof AuditEventEnum) {
            $this->event = Event::fromEnum($event);

            return $this;
        }

        $this->event = Event::fromString($event);

        return $this;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(Id|AbstractUuid|string|null $id): self
    {
        if ($id instanceof Id) {
            $this->id = $id;

            return $this;
        }

        if ($id instanceof AbstractUuid) {
            $this->id = Id::fromNullableUuid($id->toNullableString());

            return $this;
        }

        $this->id = Id::fromNullableUuid($id);

        return $this;
    }

    public function getIpAddress(): IpAddress
    {
        return $this->ipAddress;
    }

    public function setIpAddress(IpAddress|AbstractString|string|null $ipAddress): self
    {
        if ($ipAddress instanceof IpAddress) {
            $this->ipAddress = $ipAddress;

            return $this;
        }

        if ($ipAddress instanceof AbstractString) {
            $this->ipAddress = IpAddress::fromNullableString($ipAddress->toNullableString());

            return $this;
        }

        $this->ipAddress = IpAddress::fromNullableString($ipAddress);

        return $this;
    }

    public function getNewValues(): NewValues
    {
        return $this->newValues;
    }

    /**
     * @param  NewValues|Collection<array-key, mixed>|array<array-key, mixed>|null  $newValues
     */
    public function setNewValues(NewValues|Collection|array|null $newValues): self
    {
        if ($newValues instanceof NewValues) {
            $this->newValues = $newValues;

            return $this;
        }

        if ($newValues instanceof Collection) {
            $this->newValues = NewValues::fromCollection($newValues);

            return $this;
        }

        $this->newValues = NewValues::fromNullableArray($newValues);

        return $this;
    }

    public function getOldValues(): OldValues
    {
        return $this->oldValues;
    }

    /**
     * @param  OldValues|Collection<array-key, mixed>|array<array-key, mixed>|null  $oldValues
     */
    public function setOldValues(OldValues|Collection|array|null $oldValues): self
    {
        if ($oldValues instanceof OldValues) {
            $this->oldValues = $oldValues;

            return $this;
        }

        if ($oldValues instanceof Collection) {
            $this->oldValues = OldValues::fromCollection($oldValues);

            return $this;
        }

        $this->oldValues = OldValues::fromNullableArray($oldValues);

        return $this;
    }

    public function getRequestId(): RequestId
    {
        return $this->requestId;
    }

    public function setRequestId(RequestId|AbstractUuid|string|null $requestId): self
    {
        if ($requestId instanceof RequestId) {
            $this->requestId = $requestId;

            return $this;
        }

        if ($requestId instanceof AbstractUuid) {
            $this->requestId = RequestId::fromNullableUuid($requestId->toNullableString());

            return $this;
        }

        $this->requestId = RequestId::fromNullableUuid($requestId);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(TeamId|AbstractUuid|string|null $teamId): self
    {
        if ($teamId instanceof TeamId) {
            $this->teamId = $teamId;

            return $this;
        }

        if ($teamId instanceof AbstractUuid) {
            $this->teamId = TeamId::fromNullableUuid($teamId->toNullableString());

            return $this;
        }

        $this->teamId = TeamId::fromNullableUuid($teamId);

        return $this;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function setUrl(Url|AbstractString|string|null $url): self
    {
        if ($url instanceof Url) {
            $this->url = $url;

            return $this;
        }

        if ($url instanceof AbstractString) {
            $this->url = Url::fromNullableString($url->toNullableString());

            return $this;
        }

        $this->url = Url::fromNullableString($url);

        return $this;
    }

    public function getUserAgent(): UserAgent
    {
        return $this->userAgent;
    }

    public function setUserAgent(UserAgent|AbstractString|string|null $userAgent): self
    {
        if ($userAgent instanceof UserAgent) {
            $this->userAgent = $userAgent;

            return $this;
        }

        if ($userAgent instanceof AbstractString) {
            $this->userAgent = UserAgent::fromNullableString($userAgent->toNullableString());

            return $this;
        }

        $this->userAgent = UserAgent::fromNullableString($userAgent);

        return $this;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function setUserId(UserId|AbstractUuid|string|null $userId): self
    {
        if ($userId instanceof UserId) {
            $this->userId = $userId;

            return $this;
        }

        if ($userId instanceof AbstractUuid) {
            $this->userId = UserId::fromNullableUuid($userId->toNullableString());

            return $this;
        }

        $this->userId = UserId::fromNullableUuid($userId);

        return $this;
    }

    public function getActor(): Actor
    {
        return $this->actor;
    }
}
