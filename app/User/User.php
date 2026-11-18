<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Abstracts\AbstractUuid;
use App\User\Factories\UserFactory;
use App\User\Properties\CreatedAt;
use App\User\Properties\CurrentTeamId;
use App\User\Properties\Email;
use App\User\Properties\EmailVerifiedAt;
use App\User\Properties\GettingStartedDismissedAt;
use App\User\Properties\Id;
use App\User\Properties\IsPlatformAdmin;
use App\User\Properties\JobTitle;
use App\User\Properties\Name;
use App\User\Properties\OnboardedAt;
use App\User\Properties\OnboardingRole;
use App\User\Properties\OnboardingTeamSize;
use App\User\Properties\OnboardingUseCase;
use App\User\Properties\Password;
use App\User\Properties\Timezone;
use App\User\Properties\UpdatedAt;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractDate;
use Mom\Data\AbstractEnum;
use Mom\Data\AbstractString;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<UserEloquentModel>
 */
final class User extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CurrentTeamId $currentTeamId,
        public Email $email,
        public EmailVerifiedAt $emailVerifiedAt,
        public GettingStartedDismissedAt $gettingStartedDismissedAt,
        public Id $id,
        public IsPlatformAdmin $isPlatformAdmin,
        public JobTitle $jobTitle,
        public Name $name,
        public OnboardedAt $onboardedAt,
        public OnboardingRole $onboardingRole,
        public OnboardingTeamSize $onboardingTeamSize,
        public OnboardingUseCase $onboardingUseCase,
        public Password $password,
        public Timezone $timezone,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): UserFactory
    {
        return UserEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): UserCollection
    {
        if ($items instanceof UserCollection) {
            return $items;
        }

        return UserCollection::make(parent::collect($items, $method)->all());
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCurrentTeamId(): CurrentTeamId
    {
        return $this->currentTeamId;
    }

    public function setCurrentTeamId(CurrentTeamId|AbstractUuid|string|null $currentTeamId): self
    {
        if ($currentTeamId instanceof CurrentTeamId) {
            $this->currentTeamId = $currentTeamId;

            return $this;
        }

        if ($currentTeamId instanceof AbstractUuid) {
            $this->currentTeamId = CurrentTeamId::fromNullableUuid($currentTeamId->toNullableString());

            return $this;
        }

        $this->currentTeamId = CurrentTeamId::fromNullableUuid($currentTeamId);

        return $this;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email|AbstractString|string $email): self
    {
        if ($email instanceof Email) {
            $this->email = $email;

            return $this;
        }

        if (is_string($email)) {
            $this->email = Email::fromString($email);

            return $this;
        }

        $this->email = Email::fromNullableString($email->toNullableString());

        return $this;
    }

    public function getEmailVerifiedAt(): EmailVerifiedAt
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(EmailVerifiedAt|AbstractDate|DateTimeInterface|null $emailVerifiedAt): self
    {
        if ($emailVerifiedAt instanceof EmailVerifiedAt) {
            $this->emailVerifiedAt = $emailVerifiedAt;

            return $this;
        }

        if ($emailVerifiedAt instanceof AbstractDate) {
            $this->emailVerifiedAt = EmailVerifiedAt::fromNullableCarbon($emailVerifiedAt->toNullableCarbon());

            return $this;
        }

        $this->emailVerifiedAt = new EmailVerifiedAt($emailVerifiedAt);

        return $this;
    }

    public function getGettingStartedDismissedAt(): GettingStartedDismissedAt
    {
        return $this->gettingStartedDismissedAt;
    }

    public function setGettingStartedDismissedAt(GettingStartedDismissedAt|AbstractDate|DateTimeInterface|null $gettingStartedDismissedAt): self
    {
        if ($gettingStartedDismissedAt instanceof GettingStartedDismissedAt) {
            $this->gettingStartedDismissedAt = $gettingStartedDismissedAt;

            return $this;
        }

        if ($gettingStartedDismissedAt instanceof AbstractDate) {
            $this->gettingStartedDismissedAt = GettingStartedDismissedAt::fromNullableCarbon($gettingStartedDismissedAt->toNullableCarbon());

            return $this;
        }

        $this->gettingStartedDismissedAt = new GettingStartedDismissedAt($gettingStartedDismissedAt);

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

    public function getIsPlatformAdmin(): IsPlatformAdmin
    {
        return $this->isPlatformAdmin;
    }

    public function setIsPlatformAdmin(mixed $isPlatformAdmin): self
    {
        $this->isPlatformAdmin = new IsPlatformAdmin($isPlatformAdmin instanceof AbstractValue ? $isPlatformAdmin->toValue() : $isPlatformAdmin);

        return $this;
    }

    public function getJobTitle(): JobTitle
    {
        return $this->jobTitle;
    }

    public function setJobTitle(JobTitle|AbstractString|string|null $jobTitle): self
    {
        $this->jobTitle = $jobTitle instanceof JobTitle
            ? $jobTitle
            : JobTitle::fromNullableString($jobTitle instanceof AbstractString ? $jobTitle->toNullableString() : $jobTitle);

        return $this;
    }

    public function getTimezone(): Timezone
    {
        return $this->timezone;
    }

    public function setTimezone(Timezone|AbstractString|string|null $timezone): self
    {
        $this->timezone = $timezone instanceof Timezone
            ? $timezone
            : Timezone::fromNullableString($timezone instanceof AbstractString ? $timezone->toNullableString() : $timezone);

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name|AbstractString|string $name): self
    {
        if ($name instanceof Name) {
            $this->name = $name;

            return $this;
        }

        if (is_string($name)) {
            $this->name = Name::fromString($name);

            return $this;
        }

        $this->name = Name::fromNullableString($name->toNullableString());

        return $this;
    }

    public function getOnboardedAt(): OnboardedAt
    {
        return $this->onboardedAt;
    }

    public function setOnboardedAt(OnboardedAt|AbstractDate|DateTimeInterface|null $onboardedAt): self
    {
        if ($onboardedAt instanceof OnboardedAt) {
            $this->onboardedAt = $onboardedAt;

            return $this;
        }

        if ($onboardedAt instanceof AbstractDate) {
            $this->onboardedAt = OnboardedAt::fromNullableCarbon($onboardedAt->toNullableCarbon());

            return $this;
        }

        $this->onboardedAt = new OnboardedAt($onboardedAt);

        return $this;
    }

    public function getOnboardingRole(): OnboardingRole
    {
        return $this->onboardingRole;
    }

    public function setOnboardingRole(OnboardingRole|AbstractEnum|OnboardingRoleEnum|string|null $onboardingRole): self
    {
        if ($onboardingRole instanceof OnboardingRole) {
            $this->onboardingRole = $onboardingRole;

            return $this;
        }

        if ($onboardingRole instanceof AbstractEnum) {
            $this->onboardingRole = new OnboardingRole($onboardingRole->toNullableEnum());

            return $this;
        }

        $this->onboardingRole = new OnboardingRole($onboardingRole);

        return $this;
    }

    public function getOnboardingTeamSize(): OnboardingTeamSize
    {
        return $this->onboardingTeamSize;
    }

    public function setOnboardingTeamSize(OnboardingTeamSize|AbstractEnum|OnboardingTeamSizeEnum|string|null $onboardingTeamSize): self
    {
        if ($onboardingTeamSize instanceof OnboardingTeamSize) {
            $this->onboardingTeamSize = $onboardingTeamSize;

            return $this;
        }

        if ($onboardingTeamSize instanceof AbstractEnum) {
            $this->onboardingTeamSize = new OnboardingTeamSize($onboardingTeamSize->toNullableEnum());

            return $this;
        }

        $this->onboardingTeamSize = new OnboardingTeamSize($onboardingTeamSize);

        return $this;
    }

    public function getOnboardingUseCase(): OnboardingUseCase
    {
        return $this->onboardingUseCase;
    }

    public function setOnboardingUseCase(OnboardingUseCase|AbstractEnum|OnboardingUseCaseEnum|string|null $onboardingUseCase): self
    {
        if ($onboardingUseCase instanceof OnboardingUseCase) {
            $this->onboardingUseCase = $onboardingUseCase;

            return $this;
        }

        if ($onboardingUseCase instanceof AbstractEnum) {
            $this->onboardingUseCase = new OnboardingUseCase($onboardingUseCase->toNullableEnum());

            return $this;
        }

        $this->onboardingUseCase = new OnboardingUseCase($onboardingUseCase);

        return $this;
    }

    public function getPassword(): Password
    {
        return $this->password;
    }

    public function setPassword(Password|AbstractString|string $password): self
    {
        if ($password instanceof Password) {
            $this->password = $password;

            return $this;
        }

        if (is_string($password)) {
            $this->password = Password::fromString($password);

            return $this;
        }

        $this->password = Password::fromNullableString($password->toNullableString());

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
