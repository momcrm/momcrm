<?php

declare(strict_types=1);

namespace App\BankTransaction\Matching;

use App\BankAccount\BankAccount;
use App\BankTransaction\BankTransactionMatchEnum;
use App\Customer\CustomerEloquentModel;
use App\Customer\CustomerStatusEnum;
use App\Customer\Properties\Currency as CustomerCurrency;
use App\Customer\Properties\Id as CustomerId;
use App\Customer\Properties\Name as CustomerName;
use App\Customer\Properties\Number as CustomerNumber;
use App\Customer\Properties\Prefix as CustomerPrefix;
use App\Customer\Properties\Status as CustomerStatus;
use App\Customer\Properties\TeamId as CustomerTeamId;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Properties\CustomerId as InvoiceCustomerId;
use App\Invoice\Properties\Number as InvoiceNumber;
use App\Invoice\Properties\Prefix as InvoicePrefix;
use App\Invoice\Properties\Status as InvoiceStatus;
use App\Invoice\Properties\TeamId as InvoiceTeamId;
use App\Team\Actions\FindTeam;
use App\Team\Team;

/**
 * Works out which customer a deposit is from by its description, as
 * Amphora's bank feed matcher does. Two ways are tried, most certain first:
 * invoice and customer numbers quoted in the description, then the words of
 * customer names. A way that points at more than one customer settles
 * nothing, and the line is left for someone to pick rather than guessed.
 *
 * Only customers billed in the account's currency are considered, and the
 * team's customers and invoices are read once, so one matcher serves a
 * whole import.
 */
final class CustomerMatcher
{
    /**
     * How much of a word in a customer's name a description word must cover
     * to count. Tolerant of names banks cut short ("insuranc" for
     * Insurance), strict enough that "north" doesn't match "northdandalup".
     */
    private const float MINIMUM_COVERAGE = 0.6;

    /**
     * How long the only matching word must be to be trusted alone. A
     * shorter one is as likely a first name shared with another customer.
     */
    private const int MINIMUM_LONE_KEYWORD = 6;

    /**
     * Words in nearly every bank description, business name or place, which
     * identify nobody.
     *
     * @var list<string>
     */
    private const array NOISE = [
        'account', 'acct', 'and', 'app', 'aust', 'australia', 'australian',
        'bank', 'banking', 'bpay', 'company', 'credit', 'date', 'debit', 'deposit',
        'direct', 'eft', 'enterprises', 'fast', 'for', 'from', 'group', 'holdings',
        'inc', 'incorporated', 'internet', 'inv', 'invoice', 'invoices', 'llc', 'ltd',
        'limited', 'management', 'mobile', 'mrs', 'netbank', 'number', 'online',
        'osko', 'paid', 'pay', 'payid', 'payment', 'payments', 'pty', 'ptyltd', 'ref',
        'reference', 'remittance', 'reversal', 'service', 'services',
        'solutions', 'systems', 'tax', 'the', 'transfer', 'transfers',
        'trust', 'trustee', 'value', 'wrong', 'monthly', 'annual', 'renewal',
        'january', 'february', 'march', 'april', 'june', 'july', 'august',
        'september', 'october', 'november', 'december',
        'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct',
        'nov', 'dec',
        'act', 'nsw', 'qld', 'tas', 'vic',
    ];

    /**
     * What each description already matched, as statements repeat them.
     *
     * @var array<string, CustomerMatch|false>
     */
    private array $matched = [];

    /**
     * @param  array<string, string>  $names  customer name, keyed by id
     * @param  array<int, string>  $customerNumbers  customer id, keyed by customer number
     * @param  array<string, string>  $customerPrefixes  customer id, keyed by lowercased prefix
     * @param  array<string, array<int, string>>  $invoiceNumbers  invoice number => customer id, keyed by lowercased invoice prefix
     * @param  list<string>  $teamWords  words of the team's own name, which are in its own transfers
     */
    private function __construct(
        private readonly array $names,
        private readonly array $customerNumbers,
        private readonly array $customerPrefixes,
        private readonly array $invoiceNumbers,
        private readonly array $teamWords,
    ) {}

    public static function forAccount(BankAccount $account): self
    {
        $teamId = $account->getTeamId()->toString();
        $customers = CustomerEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(CustomerTeamId::getDatabaseTableColumnName(), $teamId)
            ->where(CustomerStatus::getDatabaseTableColumnName(), CustomerStatusEnum::Active->value)
            ->where(CustomerCurrency::getDatabaseTableColumnName(), $account->getCurrency()->toEnum()->value)
            ->get([CustomerId::getDatabaseTableColumnName(), CustomerName::getDatabaseTableColumnName(), CustomerNumber::getDatabaseTableColumnName(), CustomerPrefix::getDatabaseTableColumnName()]);

        $invoiceNumbers = [];

        InvoiceEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(InvoiceTeamId::getDatabaseTableColumnName(), $teamId)
            ->whereIn(InvoiceStatus::getDatabaseTableColumnName(), [InvoiceStatusEnum::Sent->value, InvoiceStatusEnum::Paid->value])
            ->whereIn(InvoiceCustomerId::getDatabaseTableColumnName(), $customers->modelKeys())
            ->get([InvoiceNumber::getDatabaseTableColumnName(), InvoicePrefix::getDatabaseTableColumnName(), InvoiceCustomerId::getDatabaseTableColumnName()])
            ->each(function (InvoiceEloquentModel $invoice) use (&$invoiceNumbers): void {
                $invoiceNumbers[self::prefixKey((string) $invoice->prefix)][$invoice->number] = $invoice->customer_id;
            });

        $team = FindTeam::byId($teamId);

        return new self(
            names: $customers->mapWithKeys(fn (CustomerEloquentModel $customer): array => [$customer->id => $customer->name])->all(),
            customerNumbers: $customers->mapWithKeys(fn (CustomerEloquentModel $customer): array => [$customer->number => $customer->id])->all(),
            customerPrefixes: $customers
                ->filter(fn (CustomerEloquentModel $customer): bool => $customer->prefix !== null && mb_strlen($customer->prefix) >= 3)
                ->mapWithKeys(fn (CustomerEloquentModel $customer): array => [mb_strtolower((string) $customer->prefix) => $customer->id])
                ->all(),
            invoiceNumbers: $invoiceNumbers,
            teamWords: self::teamWords($team),
        );
    }

    public function match(string $description): ?CustomerMatch
    {
        $match = $this->matched[$description] ??= $this->find($description) ?? false;

        return $match === false ? null : $match;
    }

    private function find(string $description): ?CustomerMatch
    {
        $searchable = self::searchable($description);
        $byNumber = $this->matchByNumber($searchable);

        if ($byNumber !== null) {
            return new CustomerMatch($byNumber, BankTransactionMatchEnum::Number);
        }

        $byName = $this->matchByName($searchable);

        return $byName === null ? null : new CustomerMatch($byName, BankTransactionMatchEnum::Name);
    }

    /**
     * Invoice numbers ("INV-0042", "INV42", or a bare "0042") and customer
     * numbers or codes quoted in the description. The match only stands if
     * they all point at one customer.
     */
    private function matchByNumber(string $searchable): ?string
    {
        $found = [];
        $prefixed = [];

        foreach ($this->invoiceNumbers as $prefix => $numbers) {
            if ($prefix === '' || preg_match_all('/(?<![a-z])'.preg_quote($prefix, '/').'[\s\-#:]*0*(\d+)/i', $searchable, $matches) === 0) {
                continue;
            }

            foreach ($matches[1] as $number) {
                $prefixed[] = (int) $number;

                if (isset($numbers[(int) $number])) {
                    $found[] = $numbers[(int) $number];
                }
            }
        }

        preg_match_all('/\b\d{4,}\b/', $searchable, $matches);

        foreach ($matches[0] as $digits) {
            $number = (int) $digits;

            if (in_array($number, $prefixed, true) || ($number >= 1990 && $number <= 2100)) {
                continue;
            }

            foreach ($this->invoiceNumbers as $numbers) {
                if (isset($numbers[$number])) {
                    $found[] = $numbers[$number];
                }
            }

            if (isset($this->customerNumbers[$number])) {
                $found[] = $this->customerNumbers[$number];
            }
        }

        foreach (self::tokens($searchable) as $token) {
            if (isset($this->customerPrefixes[$token])) {
                $found[] = $this->customerPrefixes[$token];
            }
        }

        $found = array_values(array_unique($found));

        return count($found) === 1 ? $found[0] : null;
    }

    /**
     * The words left after the noise, matched against customer names. The
     * customer matching the most of them wins; a tie goes to whichever
     * matched the earliest word, since banks put the payer first and the
     * reference after. Anything short of one clear winner is left alone.
     */
    private function matchByName(string $searchable): ?string
    {
        $keywords = $this->keywords($searchable);

        if ($keywords === []) {
            return null;
        }

        $candidates = [];

        foreach ($this->names as $customerId => $name) {
            $matched = $this->matchedKeywords($name, $keywords);

            if ($this->isConfident($matched, $searchable)) {
                $candidates[] = ['id' => $customerId, 'count' => count($matched), 'position' => $this->position($matched, $keywords)];
            }
        }

        usort($candidates, fn (array $a, array $b): int => [$b['count'], $a['position']] <=> [$a['count'], $b['position']]);

        if ($candidates === []) {
            return null;
        }

        $best = $candidates[0];
        $runnerUp = $candidates[1] ?? null;

        if ($runnerUp !== null && $runnerUp['count'] === $best['count'] && $runnerUp['position'] === $best['position']) {
            return null;
        }

        return $best['id'];
    }

    /**
     * @param  list<string>  $keywords
     * @return list<string>
     */
    private function matchedKeywords(string $name, array $keywords): array
    {
        $haystack = self::words($name);

        return array_values(array_filter($keywords, fn (string $keyword): bool => self::covers($keyword, $haystack)));
    }

    /**
     * Whether the keyword starts a word of the name and accounts for most of it.
     */
    private static function covers(string $keyword, string $haystack): bool
    {
        if (preg_match_all('/\b'.preg_quote($keyword, '/').'[a-z0-9]*/', $haystack, $matches) === 0) {
            return false;
        }

        foreach ($matches[0] as $word) {
            if (mb_strlen($keyword) / mb_strlen($word) >= self::MINIMUM_COVERAGE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Two matching words are enough. One is trusted when it is long enough
     * to name a customer alone, or when the description repeats it, as
     * banks echo the payer's name.
     *
     * @param  list<string>  $matched
     */
    private function isConfident(array $matched, string $searchable): bool
    {
        if (count($matched) >= 2) {
            return true;
        }

        if ($matched === []) {
            return false;
        }

        return mb_strlen($matched[0]) >= self::MINIMUM_LONE_KEYWORD
            || preg_match_all('/\b'.preg_quote($matched[0], '/').'\b/', self::words($searchable)) > 1;
    }

    /**
     * Where the earliest matched word sits among the description's words.
     *
     * @param  list<string>  $matched
     * @param  list<string>  $keywords
     */
    private function position(array $matched, array $keywords): int
    {
        $positions = array_map(fn (string $keyword): int => (int) array_search($keyword, $keywords, true), $matched);

        return $positions === [] ? PHP_INT_MAX : min($positions);
    }

    /**
     * @return list<string>
     */
    private function keywords(string $searchable): array
    {
        return array_values(array_unique(array_filter(
            self::tokens($searchable),
            fn (string $word): bool => mb_strlen($word) > 2
                && ! is_numeric($word)
                && ! in_array($word, self::NOISE, true)
                && ! in_array($word, $this->teamWords, true),
        )));
    }

    /**
     * The description's words, lowercased, in order.
     *
     * @return list<string>
     */
    private static function tokens(string $text): array
    {
        return array_values(array_filter(explode(' ', self::words($text)), fn (string $word): bool => $word !== ''));
    }

    /**
     * Lowercased, with anything that isn't a letter or digit as a space, so
     * underscores and punctuation separate words.
     */
    private static function words(string $value): string
    {
        return mb_trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($value)));
    }

    /**
     * Banks put the payer's BSB straight after "Direct Credit"; it looks
     * like a number worth reading, so drop it first.
     */
    private static function searchable(string $description): string
    {
        return (string) preg_replace('/^\s*direct\s+credit\s+\d+\s*/i', '', $description);
    }

    private static function prefixKey(string $prefix): string
    {
        return mb_strtolower((string) preg_replace('/[^a-z0-9]+$/i', '', $prefix));
    }

    /**
     * @return list<string>
     */
    private static function teamWords(Team $team): array
    {
        return array_values(array_filter(
            self::tokens($team->getName()->toString().' '.$team->getBillingName()->toString()),
            fn (string $word): bool => mb_strlen($word) > 2,
        ));
    }
}
