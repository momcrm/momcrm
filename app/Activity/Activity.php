<?php

declare(strict_types=1);

namespace App\Activity;

use App\Activity\Properties\CreatedAt;
use App\Activity\Properties\Details;
use App\Activity\Properties\Id;
use App\Activity\Properties\IpAddress;
use App\Activity\Properties\RequestId;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Activity\Properties\TeamId;
use App\Activity\Properties\Type;
use App\Activity\Properties\UserAgent;
use App\Activity\Properties\UserId;
use App\Shared\Abstracts\AbstractUuid;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractString;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<ActivityEloquentModel>
 */
final class Activity extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Details $details,
        public Id $id,
        public IpAddress $ipAddress,
        public RequestId $requestId,
        public SubjectId $subjectId,
        public SubjectType $subjectType,
        public TeamId $teamId,
        public Type $type,
        public UserAgent $userAgent,
        public UserId $userId,
    ) {}

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * The row as shown in activity lists.
     *
     * @return array{id: string, type: string, label: string, details: array<array-key, mixed>|null, actor: string|null, ip_address: string|null, created_at: string|null}
     */
    public function toListItem(?string $actorName): array
    {
        return [
            'id' => $this->getId()->toString(),
            'type' => $this->getType()->toString(),
            'label' => $this->getType()->toEnum()->label(),
            'details' => $this->getDetails()->toNullableArray(),
            'actor' => $actorName,
            'ip_address' => $this->getIpAddress()->toNullableString(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getDetails(): Details
    {
        return $this->details;
    }

    /**
     * @param  Details|Collection<array-key, mixed>|array<array-key, mixed>|null  $details
     */
    public function setDetails(Details|Collection|array|null $details): self
    {
        if ($details instanceof Details) {
            $this->details = $details;

            return $this;
        }

        if ($details instanceof Collection) {
            $this->details = Details::fromCollection($details);

            return $this;
        }

        $this->details = Details::fromNullableArray($details);

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

    public function getSubjectId(): SubjectId
    {
        return $this->subjectId;
    }

    public function setSubjectId(SubjectId|AbstractUuid|string|null $subjectId): self
    {
        if ($subjectId instanceof SubjectId) {
            $this->subjectId = $subjectId;

            return $this;
        }

        if ($subjectId instanceof AbstractUuid) {
            $this->subjectId = SubjectId::fromNullableUuid($subjectId->toNullableString());

            return $this;
        }

        $this->subjectId = SubjectId::fromNullableUuid($subjectId);

        return $this;
    }

    public function getSubjectType(): SubjectType
    {
        return $this->subjectType;
    }

    public function setSubjectType(SubjectType|MorphMapKeyEnum|string|null $subjectType): self
    {
        if ($subjectType instanceof SubjectType) {
            $this->subjectType = $subjectType;

            return $this;
        }

        if ($subjectType instanceof MorphMapKeyEnum) {
            $this->subjectType = SubjectType::fromEnum($subjectType);

            return $this;
        }

        $this->subjectType = SubjectType::fromNullableString($subjectType);

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

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type|ActivityTypeEnum|string $type): self
    {
        if ($type instanceof Type) {
            $this->type = $type;

            return $this;
        }

        if ($type instanceof ActivityTypeEnum) {
            $this->type = Type::fromEnum($type);

            return $this;
        }

        $this->type = Type::fromString($type);

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
}
