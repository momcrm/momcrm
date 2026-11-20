<?php

declare(strict_types=1);

namespace App\Portal;

use App\Contact\Contact;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The signed-in contact as a data object, for portal pages and forms that
 * only run behind the portal's sign-in.
 */
final class PortalContact
{
    public static function current(): Contact
    {
        $model = PortalAuth::contact();

        if ($model === null) {
            throw new HttpException(401);
        }

        return Contact::fromEloquentModel($model);
    }
}
