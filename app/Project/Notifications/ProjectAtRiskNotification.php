<?php

declare(strict_types=1);

namespace App\Project\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

/**
 * Tells a project's members it was marked at risk.
 */
final class ProjectAtRiskNotification extends InboxNotification
{
    public function __construct(
        public readonly ProjectEloquentModel $project,
        public readonly ?UserEloquentModel $actor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forProject(InboxKindEnum::Update, $this->project, $this->actor, __('marked the project at risk'));
    }
}
