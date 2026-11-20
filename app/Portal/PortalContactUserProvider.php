<?php

declare(strict_types=1);

namespace App\Portal;

use App\Contact\ContactEloquentModel;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Finds the contact signed in to the customer portal. Contacts are scoped to
 * the signed-in person's team, and in the portal that person is the contact
 * being looked up, so the lookup itself must skip the scopes.
 */
final class PortalContactUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher)
    {
        parent::__construct($hasher, ContactEloquentModel::class);
    }

    /**
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    protected function newModelQuery($model = null): Builder
    {
        $model ??= $this->createModel();

        return $model->newQueryWithoutScopes();
    }
}
