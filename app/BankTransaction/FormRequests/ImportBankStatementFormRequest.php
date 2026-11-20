<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankAccount\Actions\FindBankAccount;
use App\BankAccount\BankAccount;
use App\BankTransaction\Actions\ImportBankStatement;
use App\BankTransaction\BankTransaction;
use App\BankTransaction\Statements\CouldNotReadStatement;
use App\BankTransaction\Statements\CsvColumns;
use App\BankTransaction\Statements\StatementDate;
use App\BankTransaction\Statements\StatementNeedsColumns;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Imports one or more statement files into a bank account. When a CSV's
 * columns can't be told from the file, nothing is imported and the page is
 * sent what it needs to ask (flashed as `statementColumns`); the files come
 * back with the columns chosen.
 */
final class ImportBankStatementFormRequest extends FormRequest
{
    public const int MAX_FILES = 10;

    public const int MAX_KILOBYTES = 5120;

    public BankAccount $account;

    public function __construct()
    {
        parent::__construct();

        $this->account = BankAccount::new();
    }

    public function authorize(): Response
    {
        return Gate::authorize('import', BankTransaction::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'uuid'],
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'extensions:csv,txt,ofx,qfx'],
            'columns' => ['sometimes', 'nullable', 'array'],
            'columns.has_header' => ['sometimes', 'boolean'],
            'columns.date' => ['required_with:columns', 'integer', 'min:0'],
            'columns.description' => ['required_with:columns', 'array', 'min:1'],
            'columns.description.*' => ['integer', 'min:0'],
            'columns.amount' => ['nullable', 'integer', 'min:0'],
            'columns.credit' => ['nullable', 'integer', 'min:0'],
            'columns.debit' => ['nullable', 'integer', 'min:0'],
            'columns.date_order' => ['required_with:columns', Rule::in(StatementDate::ORDERS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => __('Choose a statement file to import.'),
            'files.*.extensions' => __('Choose a statement exported as CSV, OFX or QFX.'),
            'files.*.max' => __('A statement file can be up to 5 MB.'),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $columns = $this->input('columns');

            if (is_array($columns) && ! $validator->errors()->has('columns.*') && CsvColumns::fromStored($columns) === null) {
                $validator->errors()->add('columns.amount', __('Choose the amount column, or the credit column.'));
            }

            if ($validator->errors()->has('bank_account_id')) {
                return;
            }

            $this->account = FindBankAccount::byIdForTeam($this->string('bank_account_id')->toString(), $this->user()?->current_team_id);

            if ($this->account->isNull()) {
                $validator->errors()->add('bank_account_id', __('Choose one of your bank accounts.'));
            }
        }];
    }

    /**
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        $uploaded = $this->file('files');
        $files = [];

        foreach (is_array($uploaded) ? $uploaded : [] as $file) {
            $files[] = ['name' => $file->getClientOriginalName(), 'contents' => (string) $file->get()];
        }

        $columns = $this->input('columns');

        try {
            $imported = ImportBankStatement::intoAccount(
                $this->account,
                $files,
                is_array($columns) ? CsvColumns::fromStored($columns) : null,
            );
        } catch (CouldNotReadStatement $exception) {
            throw ValidationException::withMessages(['files' => $exception->getMessage()]);
        } catch (StatementNeedsColumns $exception) {
            Inertia::flash('statementColumns', $exception->toArray());

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $imported->summary()]);

        return back();
    }
}
