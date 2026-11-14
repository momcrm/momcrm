<?php

namespace Database\Seeders;

use App\User\Properties\Email;
use App\User\Properties\Name;
use App\User\UserEloquentModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // UserEloquentModel::factory(10)->create();

        UserEloquentModel::factory()->withPersonalTeam()->create([
            Name::getDatabaseTableColumnName() => 'Test User',
            Email::getDatabaseTableColumnName() => 'test@example.com',
        ]);
    }
}
