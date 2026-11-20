<?php

declare(strict_types=1);

namespace App\BankTransaction\Factories;

use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\BankTransaction\Properties\AmountInCents;
use App\BankTransaction\Properties\BankAccountId;
use App\BankTransaction\Properties\Description;
use App\BankTransaction\Properties\ExternalId;
use App\BankTransaction\Properties\HiddenAt;
use App\BankTransaction\Properties\PostedOn;
use App\BankTransaction\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankTransactionEloquentModel>
 */
final class BankTransactionFactory extends Factory
{
    protected $model = BankTransactionEloquentModel::class;

    /**
     * A pending deposit with no customer yet. Use forAccount() to put it in
     * an account of its team.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            BankAccountId::getDatabaseTableColumnName() => BankAccountEloquentModel::factory(),
            PostedOn::getDatabaseTableColumnName() => now()->toDateString(),
            AmountInCents::getDatabaseTableColumnName() => fake()->numberBetween(1000, 500000),
            Description::getDatabaseTableColumnName() => 'Direct Credit '.fake()->numerify('######').' '.mb_strtoupper(fake()->company()),
            ExternalId::getDatabaseTableColumnName() => fake()->unique()->uuid(),
        ];
    }

    public function forAccount(BankAccountEloquentModel $account): self
    {
        return $this->state([
            TeamId::getDatabaseTableColumnName() => $account->team_id,
            BankAccountId::getDatabaseTableColumnName() => $account->id,
        ]);
    }

    public function hidden(): self
    {
        return $this->state([HiddenAt::getDatabaseTableColumnName() => now()]);
    }
}
