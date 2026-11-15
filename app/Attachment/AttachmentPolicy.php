<?php

declare(strict_types=1);

namespace App\Attachment;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

final class AttachmentPolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Attachment;
    }
}
