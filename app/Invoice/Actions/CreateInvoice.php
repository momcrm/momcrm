<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Properties\Id;
use App\Shared\Actions\NextTeamNumber;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use App\Team\Actions\FindTeam;
use App\Team\Properties\InvoicePrefix;
use Illuminate\Support\Facades\DB;

/**
 * Creates a draft invoice, numbered within its team and with the team's
 * invoice prefix, with its line items.
 */
final readonly class CreateInvoice
{
    /**
     * @param  array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id?: ?string, rental_starts_on?: ?string, rental_ends_on?: ?string}>  $items
     */
    public function __construct(
        private Invoice $invoice,
        private array $items,
    ) {}

    public function __invoke(): Invoice
    {
        return DB::transaction(function (): Invoice {
            if ($this->invoice->getId()->isNull()) {
                $this->invoice->setId(Id::generate());
            }

            if ($this->invoice->getCurrency()->isNull()) {
                $this->invoice->setCurrency(CurrencyEnum::default()->value);
            }

            $this->invoice
                ->setPrefix(FindTeam::byId($this->invoice->getTeamId()->toString())->getInvoicePrefix()->toNullableString() ?? InvoicePrefix::DEFAULT)
                ->setStatus(InvoiceStatusEnum::Draft)
                ->setNumber(NextTeamNumber::for(Table::Invoices, $this->invoice->getTeamId()->toString()));

            $model = InvoiceEloquentModel::query()->create($this->invoice->forDatabaseCreate());

            SaveInvoiceItems::forInvoice($this->invoice, $this->items);

            return Invoice::fromEloquentModel($model->refresh());
        });
    }

    /**
     * @param  array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id?: ?string, rental_starts_on?: ?string, rental_ends_on?: ?string}>  $items
     */
    public static function fromInvoice(Invoice $invoice, array $items): Invoice
    {
        return new self($invoice, $items)->handle();
    }

    public function handle(): Invoice
    {
        return $this->__invoke();
    }

    public function execute(): Invoice
    {
        return $this->__invoke();
    }
}
