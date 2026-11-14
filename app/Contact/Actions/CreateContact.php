<?php

declare(strict_types=1);

namespace App\Contact\Actions;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Contact\Properties\Id;
use Illuminate\Support\Facades\DB;

final readonly class CreateContact
{
    public function __construct(
        private Contact $contact,
    ) {}

    public function __invoke(): Contact
    {
        return DB::transaction(function (): Contact {
            if ($this->contact->getId()->isNull()) {
                $this->contact->setId(Id::generate());
            }

            $model = ContactEloquentModel::query()->create($this->contact->forDatabaseCreate());

            KeepOnePrimaryContact::forContact($this->contact);

            return Contact::fromEloquentModel($model->refresh());
        });
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
