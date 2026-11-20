<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

function csvFile(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('customers.csv', $content);
}

it('imports customers and their contacts, matching common headings', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $csv = "\u{FEFF}Company Name,E-mail,Phone,Contact,Contact Email,Notes\n"
        ."Acme IT,hi@acme.test,08 9000 0000,Sam Lee,sam@acme.test,ignored\n"
        ."Acme IT,,,Kim Wu,kim@acme.test,\n"
        ."Zeta Pty Ltd,ops@zeta.test,,,,\n";

    actingAs($user)
        ->post(route('customers.import.store'), ['file' => csvFile($csv)])
        ->assertRedirectToRoute('customers.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Imported 2 customers. 2 contacts came with them.']);

    $acme = CustomerEloquentModel::query()->where('name', 'Acme IT')->sole();
    expect($acme)
        ->team_id->toBe($user->current_team_id)
        ->email->toBe('hi@acme.test')
        ->number->toBe(1);
    expect(ContactEloquentModel::query()->where('customer_id', $acme->id)->orderBy('first_name')->get(['first_name', 'last_name', 'email', 'is_primary'])->toArray())
        ->toBe([
            ['first_name' => 'Kim', 'last_name' => 'Wu', 'email' => 'kim@acme.test', 'is_primary' => false],
            ['first_name' => 'Sam', 'last_name' => 'Lee', 'email' => 'sam@acme.test', 'is_primary' => true],
        ]);
    expect(CustomerEloquentModel::query()->where('name', 'Zeta Pty Ltd')->sole()->email)->toBe('ops@zeta.test');
});

it('imports tax IDs, naming them from their heading unless a label column says otherwise', function (string $headings, string $row, string $label, string $taxId): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('customers.import.store'), ['file' => csvFile("{$headings}\n{$row}\n")])
        ->assertSessionHasNoErrors();

    expect(CustomerEloquentModel::query()->sole())
        ->tax_id->toBe($taxId)
        ->tax_id_label->toBe($label);
})->with([
    'an ABN column' => ['Name,ABN', 'Acme IT,12 345 678 901', 'ABN', '12 345 678 901'],
    'a VAT number column' => ['Name,VAT Number', 'Acme Ltd,GB123456789', 'VAT', 'GB123456789'],
    'a label column' => ['Name,Tax ID,Tax ID label', 'Acme Inc,12-3456789,EIN', 'EIN', '12-3456789'],
]);

it('imports nothing and names the rows when any row is invalid', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $csv = "Name,Email\nAcme IT,hi@acme.test\n,missing@name.test\nZeta,not-an-email\n";

    actingAs($user)
        ->post(route('customers.import.store'), ['file' => csvFile($csv)])
        ->assertSessionHasErrors(['file' => "Row 3 has no customer name. Row 4: not-an-email isn't a valid email address."]);

    expect(CustomerEloquentModel::query()->exists())->toBeFalse();
});

it('needs a column for the customer name', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.import.store'), ['file' => csvFile("Email,Phone\nhi@acme.test,1\n")])
        ->assertSessionHasErrors(['file' => 'We need a column for the customer name, headed Name, Customer or Company.']);
});

it('rejects a file with headings but no rows', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.import.store'), ['file' => csvFile("Name\n")])
        ->assertSessionHasErrors(['file' => 'That file has a heading row but no customers under it.']);
});

it('requires a file', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.import.store'), [])
        ->assertSessionHasErrors(['file' => 'Choose a CSV file to import.']);
});
