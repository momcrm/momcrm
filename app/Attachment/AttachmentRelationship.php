<?php

declare(strict_types=1);

namespace App\Attachment;

use App\Shared\Traits\Enum;

enum AttachmentRelationship: string
{
    use Enum;

    case Attachable = 'attachable';
    case Uploader = 'uploader';
}
