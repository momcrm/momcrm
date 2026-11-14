<?php

declare(strict_types=1);

namespace App\Contact\Factories;

use App\Contact\ContactEloquentModel;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Email;
use App\Contact\Properties\FirstName;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\LastName;
use App\Contact\Properties\MobilePhone;
use App\Contact\Properties\Position;
use App\Contact\Properties\TeamId;
use App\Contact\Properties\WorkPhone;
use App\Customer\CustomerEloquentModel;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEloquentModel>
 */
final class ContactFactory extends Factory
{
    protected $model = ContactEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            FirstName::getDatabaseTableColumnName() => fake()->firstName(),
            LastName::getDatabaseTableColumnName() => fake()->lastName(),
            Email::getDatabaseTableColumnName() => fake()->safeEmail(),
            Position::getDatabaseTableColumnName() => fake()->jobTitle(),
            WorkPhone::getDatabaseTableColumnName() => fake()->phoneNumber(),
            MobilePhone::getDatabaseTableColumnName() => null,
            IsPrimary::getDatabaseTableColumnName() => false,
        ];
    }
}
