<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\AccessRule\Engine\AccessEvaluator;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractData;

/**
 * A policy whose every ability is decided by the team's access rules. Each
 * domain's policy names its resource; abilities map to AccessActionEnum.
 * Create and import take the DTO class: Gate::authorize('create', Project::class).
 */
abstract class AbstractAccessPolicy
{
    abstract protected function resource(): AccessResourceEnum;

    public function create(UserEloquentModel $user): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Create);
    }

    public function import(UserEloquentModel $user): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Import);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function archive(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Archive, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function changeStatus(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::ChangeStatus, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function changeVisibility(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::ChangeVisibility, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function close(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Close, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function delete(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Delete, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function manageMembers(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::ManageMembers, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function move(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Move, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function reopen(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Reopen, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function restore(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Restore, $record);
    }

    /**
     * @param  AbstractData<covariant Model>  $record
     */
    public function update(UserEloquentModel $user, AbstractData $record): Response
    {
        return AccessEvaluator::decide($user, $this->resource(), AccessActionEnum::Update, $record);
    }
}
