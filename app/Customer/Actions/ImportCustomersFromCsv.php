<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Contact\Actions\CreateContact;
use App\Contact\Contact;
use App\Customer\Customer;
use App\Customer\CustomerStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Imports customers, and optionally one contact per row, from a spreadsheet
 * exported as CSV. Columns are matched by their heading, so most exports work
 * as they are. Rows with the same customer name become one customer with
 * several contacts.
 *
 * The import is all or nothing: any invalid row is reported and nothing is saved.
 */
final readonly class ImportCustomersFromCsv
{
    public const int MAX_ROWS = 1000;

    /**
     * Accepted headings for each field, compared after lowercasing and
     * replacing anything that is not a letter or digit with a space.
     *
     * @var array<string, array<int, string>>
     */
    public const array HEADINGS = [
        'name' => ['name', 'customer', 'customer name', 'company', 'company name', 'business', 'business name', 'organisation', 'organization', 'client', 'client name'],
        'tax_id' => ['tax id', 'tax number', 'tax no', 'abn', 'vat', 'vat number', 'vat no', 'gst number', 'gst no', 'nzbn', 'uen', 'tin', 'ein', 'business number'],
        'tax_id_label' => ['tax id label', 'tax id type'],
        'email' => ['email', 'e mail', 'customer email', 'company email', 'email address'],
        'phone' => ['phone', 'customer phone', 'company phone', 'telephone', 'phone number'],
        'website' => ['website', 'web', 'url', 'web site'],
        'contact_name' => ['contact', 'contact name'],
        'contact_first_name' => ['first name', 'contact first name', 'firstname', 'given name'],
        'contact_last_name' => ['last name', 'contact last name', 'lastname', 'surname', 'family name'],
        'contact_email' => ['contact email'],
        'contact_phone' => ['contact phone', 'mobile', 'contact mobile', 'mobile phone'],
    ];

    /**
     * What a tax ID heading says the tax ID is called, when it says.
     *
     * @var array<string, string>
     */
    public const array TAX_ID_LABELS = [
        'abn' => 'ABN',
        'vat' => 'VAT',
        'vat number' => 'VAT',
        'vat no' => 'VAT',
        'gst number' => 'GST No.',
        'gst no' => 'GST No.',
        'nzbn' => 'NZBN',
        'uen' => 'UEN',
        'tin' => 'TIN',
        'ein' => 'EIN',
    ];

    public function __construct(
        private string $path,
        private string $teamId,
        private string $userId,
    ) {}

    /**
     * @return array{customers: int, contacts: int}
     *
     * @throws ValidationException
     */
    public function __invoke(): array
    {
        $rows = $this->readRows();
        $grouped = $this->validateAndGroup($rows);

        return DB::transaction(function () use ($grouped): array {
            $contacts = 0;

            foreach ($grouped as $group) {
                $customer = CreateCustomer::fromCustomer(Customer::new()
                    ->setTeamId($this->teamId)
                    ->setName($group['name'])
                    ->setTaxId($group['tax_id'])
                    ->setTaxIdLabel($group['tax_id_label'])
                    ->setEmail($group['email'])
                    ->setPhone($group['phone'])
                    ->setWebsite($group['website'])
                    ->setStatus(CustomerStatusEnum::Active)
                    ->setIsSample(false));

                foreach ($group['contacts'] as $index => $person) {
                    CreateContact::fromContact(Contact::new()
                        ->setTeamId($this->teamId)
                        ->setCustomerId($customer->getId())
                        ->setFirstName($person['first_name'])
                        ->setLastName($person['last_name'])
                        ->setEmail($person['email'])
                        ->setMobilePhone($person['phone'])
                        ->setIsPrimary($index === 0));
                    $contacts++;
                }
            }

            $result = ['customers' => count($grouped), 'contacts' => $contacts];

            RecordActivity::for(type: ActivityTypeEnum::CustomersImported, userId: $this->userId, teamId: $this->teamId, details: $result);

            return $result;
        });
    }

    /**
     * @return array{customers: int, contacts: int}
     *
     * @throws ValidationException
     */
    public static function fromFile(string $path, string $teamId, string $userId): array
    {
        return new self($path, $teamId, $userId)->handle();
    }

    /**
     * @return array{customers: int, contacts: int}
     *
     * @throws ValidationException
     */
    public function handle(): array
    {
        return $this->__invoke();
    }

    /**
     * @return array{customers: int, contacts: int}
     *
     * @throws ValidationException
     */
    public function execute(): array
    {
        return $this->__invoke();
    }

    /**
     * The data rows keyed by field, with their line number in the file.
     *
     * @return array<int, array<string, string>>
     *
     * @throws ValidationException
     */
    private function readRows(): array
    {
        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw self::error(__("We couldn't read that file. Export it from your spreadsheet as CSV and try again."));
        }

        $header = fgetcsv($handle, escape: '');

        if ($header === false || $header === [null]) {
            fclose($handle);

            throw self::error(__('That file is empty.'));
        }

        $columns = $this->matchColumns($header);
        $taxIdColumn = array_search('tax_id', $columns, true);
        $labelFromHeading = $taxIdColumn === false ? null : (self::TAX_ID_LABELS[self::normalise($header[$taxIdColumn])] ?? null);

        if (! in_array('name', $columns, true)) {
            fclose($handle);

            throw self::error(__('We need a column for the customer name, headed Name, Customer or Company.'));
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if ($values === [null] || implode('', array_map(strval(...), $values)) === '') {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);

                throw self::error(__('You can import up to :max rows at a time. Split the file and import each part.', ['max' => self::MAX_ROWS]));
            }

            $row = ['line' => (string) $line];

            foreach ($columns as $index => $field) {
                if ($field !== null) {
                    $row[$field] = trim((string) ($values[$index] ?? ''));
                }
            }

            // A column headed ABN or VAT says what its tax IDs are called.
            if ($labelFromHeading !== null && ($row['tax_id_label'] ?? '') === '') {
                $row['tax_id_label'] = $labelFromHeading;
            }

            $rows[] = $row;
        }

        fclose($handle);

        if ($rows === []) {
            throw self::error(__('That file has a heading row but no customers under it.'));
        }

        return $rows;
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<int, string|null>
     */
    private function matchColumns(array $header): array
    {
        $taken = [];

        return array_map(function (?string $heading) use (&$taken): ?string {
            $normalised = self::normalise($heading);

            foreach (self::HEADINGS as $field => $headings) {
                if (! isset($taken[$field]) && in_array($normalised, $headings, true)) {
                    $taken[$field] = true;

                    return $field;
                }
            }

            return null;
        }, $header);
    }

    /**
     * A heading lowercased, with anything but letters and digits made a
     * single space.
     */
    private static function normalise(?string $heading): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower(preg_replace('/^\xEF\xBB\xBF/', '', (string) $heading) ?? '')));
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array{name: string, tax_id: ?string, tax_id_label: ?string, email: ?string, phone: ?string, website: ?string, contacts: array<int, array{first_name: string, last_name: ?string, email: ?string, phone: ?string}>}>
     *
     * @throws ValidationException
     */
    private function validateAndGroup(array $rows): array
    {
        $errors = [];
        $grouped = [];

        foreach ($rows as $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'tax_id' => ['nullable', 'string', 'max:50'],
                'tax_id_label' => ['nullable', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'website' => ['nullable', 'url', 'max:255'],
                'contact_email' => ['nullable', 'email', 'max:255'],
            ], [
                'name.required' => __('Row :line has no customer name.', ['line' => $row['line']]),
                'email.email' => __("Row :line: :value isn't a valid email address.", ['line' => $row['line'], 'value' => $row['email'] ?? '']),
                'contact_email.email' => __("Row :line: :value isn't a valid email address.", ['line' => $row['line'], 'value' => $row['contact_email'] ?? '']),
                'website.url' => __('Row :line: the website should start with https://.', ['line' => $row['line']]),
                '*.max' => __('Row :line has a value that is too long.', ['line' => $row['line']]),
            ]);

            if ($validator->fails()) {
                $errors[] = $validator->errors()->first();

                continue;
            }

            $key = mb_strtolower($row['name']);
            $grouped[$key] ??= [
                'name' => $row['name'],
                'tax_id' => ($row['tax_id'] ?? '') ?: null,
                'tax_id_label' => ($row['tax_id'] ?? '') === '' ? null : (($row['tax_id_label'] ?? '') ?: null),
                'email' => ($row['email'] ?? '') ?: null,
                'phone' => ($row['phone'] ?? '') ?: null,
                'website' => ($row['website'] ?? '') ?: null,
                'contacts' => [],
            ];

            $contact = $this->contactFrom($row);

            if ($contact !== null) {
                $grouped[$key]['contacts'][] = $contact;
            }
        }

        if ($errors !== []) {
            $shown = array_slice($errors, 0, 5);
            $more = count($errors) - count($shown);

            throw self::error(implode(' ', $shown).($more > 0 ? ' '.__('And :count more.', ['count' => $more]) : ''));
        }

        return $grouped;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{first_name: string, last_name: ?string, email: ?string, phone: ?string}|null
     */
    private function contactFrom(array $row): ?array
    {
        $first = $row['contact_first_name'] ?? '';
        $last = $row['contact_last_name'] ?? '';

        if ($first === '' && ($row['contact_name'] ?? '') !== '') {
            [$first, $last] = array_pad(explode(' ', $row['contact_name'], 2), 2, '');
        }

        if ($first === '') {
            return null;
        }

        return [
            'first_name' => $first,
            'last_name' => $last ?: null,
            'email' => ($row['contact_email'] ?? '') ?: null,
            'phone' => ($row['contact_phone'] ?? '') ?: null,
        ];
    }

    private static function error(string $message): ValidationException
    {
        return ValidationException::withMessages(['file' => $message]);
    }
}
