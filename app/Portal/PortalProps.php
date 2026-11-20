<?php

declare(strict_types=1);

namespace App\Portal;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessEvaluator;
use App\Contact\Contact;
use App\Customer\Actions\FindCustomer;
use App\Portal\Actions\FindPortalContacts;
use App\Team\Actions\FindTeam;

/**
 * What every portal page knows: who is signed in, for which customer of which
 * provider, and which sections the access rules let them see.
 */
final class PortalProps
{
    /**
     * The sections of the portal and the kind of record each one lists.
     */
    public const array SECTIONS = [
        'tickets' => AccessResourceEnum::Ticket,
        'invoices' => AccessResourceEnum::Invoice,
        'receipts' => AccessResourceEnum::Payment,
        'statement' => AccessResourceEnum::Statement,
        'services' => AccessResourceEnum::Service,
        'assets' => AccessResourceEnum::Asset,
        'articles' => AccessResourceEnum::Article,
    ];

    /**
     * @return array{contact: array{id: string, name: string, email: ?string}, customer: array{id: string, name: string}, team: array{name: string}, sections: array<string, bool>, canSwitch: bool}|null
     */
    public static function forSignedIn(): ?array
    {
        $model = PortalAuth::contact();

        if ($model === null) {
            return null;
        }

        $contact = Contact::fromEloquentModel($model);
        $customer = FindCustomer::byIdForTeam($model->customer_id, $model->team_id);

        return [
            'contact' => [
                'id' => $model->id,
                'name' => $contact->getFullName(),
                'email' => $model->email,
            ],
            'customer' => [
                'id' => $model->customer_id,
                'name' => $customer->getName()->toString(),
            ],
            'team' => [
                'name' => FindTeam::byId($model->team_id)->getName()->toString(),
            ],
            'sections' => array_map(
                fn (AccessResourceEnum $resource): bool => AccessEvaluator::contactMayOnSome($model->id, $resource, AccessActionEnum::View),
                self::SECTIONS,
            ),
            'canSwitch' => FindPortalContacts::forEmail($model->email)->count() > 1,
        ];
    }
}
