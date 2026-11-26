<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use App\Attachment\Actions\CreateAttachment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * The mail Cloudflare has handed over for one address, kept whole on the
 * files disk until it has been taken in. Cloudflare keeps no copy, so this
 * is the inbox: what a read lists and fetches.
 *
 * An email is known by when it arrived and what it is, such as
 * "1795000000000000-9e107d9d372bb6826bd81d3542a419d6", so handing the same
 * email over twice keeps it once.
 */
final readonly class DeliveredMail
{
    /**
     * How long an email is kept. Mail is taken in within minutes; the rest
     * of the time is for a team whose mailboxes are not being read.
     */
    public const int DAYS_KEPT = 7;

    private const string ID_PATTERN = '/^(\d{16})-([a-f0-9]{32})$/';

    private function __construct(
        private string $folder,
    ) {}

    public static function forAddress(string $address): self
    {
        return new self('delivered-mail/'.hash('sha256', mb_strtolower($address)));
    }

    /**
     * Keeps an email as it arrived and gives the id it is known by. Each
     * arrival also clears out the mail that has been kept long enough.
     */
    public function keep(string $source): string
    {
        $hash = hash('xxh128', $source);
        $expired = CarbonImmutable::now()->subDays(self::DAYS_KEPT);

        foreach ($this->all() as $kept) {
            if ($kept['received_at']->lessThan($expired)) {
                self::disk()->delete($this->path($kept['id']));
            } elseif (str_ends_with($kept['id'], "-{$hash}")) {
                return $kept['id'];
            }
        }

        $id = sprintf('%016d-%s', CarbonImmutable::now()->getPreciseTimestamp(), $hash);
        self::disk()->put($this->path($id), $source);

        return $id;
    }

    /**
     * What arrived between two times, oldest first, and no more than the limit.
     *
     * @return list<array{id: string, received_at: CarbonImmutable}>
     */
    public function between(CarbonImmutable $since, CarbonImmutable $until, int $limit): array
    {
        $arrived = array_values(array_filter(
            $this->all(),
            fn (array $kept): bool => $kept['received_at']->betweenIncluded($since, $until),
        ));

        usort($arrived, fn (array $a, array $b): int => $a['id'] <=> $b['id']);

        return array_slice($arrived, 0, $limit);
    }

    /**
     * An email as it arrived; null when it was never kept or has been cleared out.
     */
    public function source(string $id): ?string
    {
        return self::receivedAt($id) === null ? null : self::disk()->get($this->path($id));
    }

    /**
     * When the email known by an id arrived; null when that is not an id.
     */
    public static function receivedAt(string $id): ?CarbonImmutable
    {
        return preg_match(self::ID_PATTERN, $id, $matches) === 1
            ? CarbonImmutable::createFromTimestampUTC(intdiv((int) $matches[1], 1_000_000))
            : null;
    }

    /**
     * @return list<array{id: string, received_at: CarbonImmutable}>
     */
    private function all(): array
    {
        $kept = [];

        foreach (self::disk()->files($this->folder) as $file) {
            $id = basename($file, '.eml');
            $receivedAt = self::receivedAt($id);

            if ($receivedAt !== null) {
                $kept[] = ['id' => $id, 'received_at' => $receivedAt];
            }
        }

        return $kept;
    }

    private function path(string $id): string
    {
        return "{$this->folder}/{$id}.eml";
    }

    /**
     * The disk uploaded files are kept on, which every server of this
     * installation reaches: mail is handed to one and taken in on another.
     */
    private static function disk(): Filesystem
    {
        return Storage::disk(CreateAttachment::disk());
    }
}
