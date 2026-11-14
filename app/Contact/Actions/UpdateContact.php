<?php

declare(strict_types=1);

namespace App\Contact\Actions;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateContact
{
    public function __construct(
        private Contact $contact,
    ) {}

    public function __invoke(): Contact
    {
        ContactEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->contact->getId()->toString())
            ?->update($this->contact->forDatabaseUpdate());

        KeepOnePrimaryContact::forContact($this->contact);

        return $this->contact;
    }

    public static function fromContact(Contact $contact): Contact
    {
        return new self($contact)->handle();
    }

    public function handle(): Contact
    {
        return $this->__invoke();
    }

    public function execute(): Contact
    {
        return $this->__invoke();
    }
}
