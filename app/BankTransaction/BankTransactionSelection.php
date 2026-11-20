<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\BankTransaction\Actions\FindBankTransaction;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * The bank feed lines a bulk change was asked for, each authorized as if it
 * were changed on its own. If any one is missing or refused, none change.
 */
final class BankTransactionSelection
{
    public const string IDS = 'bank_transaction_ids';

    public const int MAX = 200;

    /**
     * @throws CouldNotFindBankTransaction
     */
    public static function authorize(FormRequest $request, string $ability): BankTransactionCollection
    {
        $input = $request->input(self::IDS);
        $ids = array_values(array_unique(array_filter(
            is_array($input) ? $input : [],
            fn (mixed $id): bool => is_string($id) && Str::isUuid($id),
        )));

        if ($ids === [] || count($ids) > self::MAX) {
            return new BankTransactionCollection([]);
        }

        $transactions = FindBankTransaction::byIdsForTeam((string) $request->user()?->current_team_id, $ids);

        if ($transactions->count() !== count($ids)) {
            throw CouldNotFindBankTransaction::notFound();
        }

        foreach ($transactions as $transaction) {
            Gate::authorize($ability, $transaction);
        }

        return $transactions;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            self::IDS => ['required', 'array', 'min:1', 'max:'.self::MAX],
            self::IDS.'.*' => ['uuid', 'distinct'],
        ];
    }
}
