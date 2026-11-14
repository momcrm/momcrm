<?php

declare(strict_types=1);

namespace App\EmailCode\Factories;

use App\EmailCode\EmailCodeEloquentModel;
use App\EmailCode\EmailCodePurposeEnum;
use App\EmailCode\Properties\Attempts;
use App\EmailCode\Properties\CodeHash;
use App\EmailCode\Properties\ConsumedAt;
use App\EmailCode\Properties\Email;
use App\EmailCode\Properties\ExpiresAt;
use App\EmailCode\Properties\Purpose;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailCodeEloquentModel>
 */
final class EmailCodeFactory extends Factory
{
    protected $model = EmailCodeEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Email::getDatabaseTableColumnName() => fake()->safeEmail(),
            Purpose::getDatabaseTableColumnName() => EmailCodePurposeEnum::SignIn->value,
            CodeHash::getDatabaseTableColumnName() => hash('sha256', '123456'),
            Attempts::getDatabaseTableColumnName() => 0,
            ExpiresAt::getDatabaseTableColumnName() => now()->addMinutes(10),
            ConsumedAt::getDatabaseTableColumnName() => null,
        ];
    }
}
