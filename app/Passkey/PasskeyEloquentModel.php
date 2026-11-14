<?php

declare(strict_types=1);

namespace App\Passkey;

use App\Shared\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passkeys\Passkey;

/**
 * The passkeys package's model with UUID primary keys, registered in
 * AppServiceProvider via Passkeys::usePasskeyModel().
 */
final class PasskeyEloquentModel extends Passkey
{
    use HasUuids;

    public function getTable(): string
    {
        return Table::Passkeys->value;
    }
}
