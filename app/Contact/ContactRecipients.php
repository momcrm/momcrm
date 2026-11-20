<?php

declare(strict_types=1);

namespace App\Contact;

use App\Contact\Actions\FindContact;
use App\Shared\Types;
use Illuminate\Validation\Validator;

/**
 * The customer's contacts a document (a receipt, a statement) can be
 * emailed to: those with an email address, the primary contact first.
 */
final class ContactRecipients
{
    public const string FIELD = 'contact_ids';

    /**
     * @return list<array{id: string, name: string, email: string, is_primary: bool}>
     */
    public static function optionsFor(string $customerId, string $teamId): array
    {
        return array_values(FindContact::forCustomer($customerId, $teamId)
            ->filter(fn (Contact $contact): bool => $contact->getEmail()->isNotNull())
            ->map(fn (Contact $contact): array => [
                'id' => $contact->getId()->toString(),
                'name' => $contact->getFullName(),
                'email' => $contact->getEmail()->toString(),
                'is_primary' => $contact->getIsPrimary()->toBoolean(),
            ])
            ->all());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            self::FIELD => ['required', 'array', 'min:1'],
            self::FIELD.'.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            self::FIELD.'.required' => __('Choose who to send it to.'),
            self::FIELD.'.min' => __('Choose who to send it to.'),
        ];
    }

    /**
     * Adds an error unless every chosen contact is one of the customer's
     * with an email address.
     */
    public static function check(Validator $validator, string $customerId, string $teamId): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $chosen = Types::stringList($validator->getData()[self::FIELD] ?? []);

        if (count(self::emails($chosen, $customerId, $teamId)) !== count($chosen)) {
            $validator->errors()->add(self::FIELD, __('Choose contacts of this customer who have an email address.'));
        }
    }

    /**
     * The email addresses of the chosen contacts, in the order chosen.
     * Contacts that can't be emailed are left out.
     *
     * @param  list<string>  $contactIds
     * @return list<string>
     */
    public static function emails(array $contactIds, string $customerId, string $teamId): array
    {
        $emails = array_column(self::optionsFor($customerId, $teamId), 'email', 'id');

        return array_values(array_filter(array_map(fn (string $id): ?string => $emails[$id] ?? null, $contactIds)));
    }
}
