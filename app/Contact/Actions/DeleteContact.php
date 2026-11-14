<?php

declare(strict_types=1);

namespace App\Contact\Actions;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteContact
{
    public function __construct(
        private Contact $contact,
    ) {}

    public function __invoke(): void
    {
        ContactEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->contact->getId()->toString())
            ?->delete();
    }

    public static function fromContact(Contact $contact): void
    {
        new self($contact)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
