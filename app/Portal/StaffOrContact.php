<?php

declare(strict_types=1);

namespace App\Portal;

use App\Onboarding\Middleware\EnsureOnboarded;
use App\Portal\Controllers\ServeStaffOrContactController;

/**
 * A route for an address staff and customers' contacts share, such as
 * /tickets. A contact signed in to the portal gets the portal's page, behind
 * the portal's middleware; everyone else gets the staff page, behind the
 * staff middleware given here.
 */
final class StaffOrContact
{
    /**
     * What the app's own pages sit behind.
     */
    public const array STAFF_MIDDLEWARE = ['auth', 'verified', EnsureOnboarded::class];

    public const string STAFF = 'staff';

    public const string CONTACT = 'contact';

    public const string STAFF_ONLY_MIDDLEWARE = 'staff_middleware';

    /**
     * The route action. The staff middleware is left out of the route itself,
     * including any of its group's, and run only for staff.
     *
     * @param  class-string|array{class-string, string}  $staff
     * @param  class-string  $contact
     * @param  list<string>  $staffMiddleware
     * @return array{uses: string, staff: class-string|array{class-string, string}, contact: class-string, staff_middleware: list<string>, excluded_middleware: list<string>}
     */
    public static function serve(string|array $staff, string $contact, array $staffMiddleware = self::STAFF_MIDDLEWARE): array
    {
        return [
            'uses' => ServeStaffOrContactController::class.'@__invoke',
            self::STAFF => $staff,
            self::CONTACT => $contact,
            self::STAFF_ONLY_MIDDLEWARE => $staffMiddleware,
            'excluded_middleware' => $staffMiddleware,
        ];
    }
}
