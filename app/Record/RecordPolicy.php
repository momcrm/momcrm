<?php

declare(strict_types=1);

namespace App\Record;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessEvaluator;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\Response;

final class RecordPolicy extends AbstractAccessPolicy
{
    /**
     * Unlike other records, a new one is checked with a blank record that
     * says which object it will belong to, so a rule about one object
     * ("Members can't add Licences") applies to adding as well:
     * Gate::authorize('create', [Record::class, $blank]).
     */
    public function create(UserEloquentModel $user, ?Record $blank = null): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Create, $blank);
    }

    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Record;
    }
}
