<?php

declare(strict_types=1);

namespace App\Environment;

use App\Environment\Exceptions\CouldNotWriteEnvironmentFile;
use Carbon\CarbonImmutable;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Parser\Parser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * The application's `.env`, read and edited one variable at a time.
 *
 * Only the variable being changed is rewritten: every other line — comments,
 * blank lines, quoting, `export` prefixes — is written back byte for byte. The
 * file is changed in place under an exclusive lock, so it keeps its owner and
 * mode, and each change first copies the old file to the backup directory.
 *
 * Laravel loads `.env` immutably, so the first definition of a key wins; a set
 * rewrites that one and drops any later duplicates, which never took effect.
 */
final readonly class EnvironmentFile
{
    private const string KEY_PATTERN = '/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_.]*)\s*=\s?(.*)$/s';

    /**
     * Unquoted values stay unquoted while they are made of these; anything else
     * is double-quoted.
     */
    private const string BARE_VALUE_PATTERN = '/^[A-Za-z0-9_.,:\/@+\-=%~]+$/';

    public function __construct(
        public string $path,
        public string $backupDirectory,
        public int $backupsKept = 20,
    ) {}

    /**
     * How a value is written after `KEY=`. Double quotes keep `${OTHER}`
     * references working, and phpdotenv reads a newline inside them as part of
     * the value.
     */
    public static function encode(string $value): string
    {
        if ($value === '' || preg_match(self::BARE_VALUE_PATTERN, $value) === 1) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    /**
     * Every variable in file order, first definition only.
     *
     * @return Collection<int, array{key: string, value: string}>
     */
    public function variables(): Collection
    {
        $variables = [];

        foreach ($this->segments($this->read()) as ['key' => $key, 'value' => $value]) {
            if ($key !== null && $value !== null && ! array_key_exists($key, $variables)) {
                $variables[$key] = ['key' => $key, 'value' => $value];
            }
        }

        return Collection::make(array_values($variables));
    }

    public function has(string $key): bool
    {
        return $this->variables()->contains('key', $key);
    }

    public function get(string $key): ?string
    {
        return $this->variables()->firstWhere('key', $key)['value'] ?? null;
    }

    public function set(string $key, string $value): void
    {
        $this->change(function (array $segments) use ($key, $value): array {
            $line = $key.'='.self::encode($value);
            $written = false;

            foreach ($segments as $index => $segment) {
                if ($segment['key'] !== $key) {
                    continue;
                }

                if ($written) {
                    unset($segments[$index]);
                } else {
                    $segments[$index]['lines'] = [$line];
                    $written = true;
                }
            }

            if (! $written) {
                $segments[] = ['key' => $key, 'value' => $value, 'lines' => [$line]];
            }

            return $segments;
        });
    }

    public function unset(string $key): void
    {
        $this->change(fn (array $segments): array => array_filter(
            $segments,
            fn (array $segment): bool => $segment['key'] !== $key,
        ));
    }

    public function modifiedAt(): ?CarbonImmutable
    {
        clearstatcache(true, $this->path);

        $modified = @filemtime($this->path);

        return $modified === false ? null : CarbonImmutable::createFromTimestamp($modified);
    }

    /**
     * The value as phpdotenv reads it — quotes, escapes and trailing comments
     * resolved — but with `${OTHER}` references left as written.
     */
    private static function decode(string $raw): string
    {
        try {
            $entries = new Parser()->parse('VALUE='.$raw);
        } catch (InvalidFileException) {
            return $raw;
        }

        return $entries[0]->getValue()->map(fn ($value): string => $value->getChars())->getOrElse('');
    }

    private static function closesDoubleQuote(string $text): bool
    {
        return preg_match('/(?<!\\\\)(?:\\\\\\\\)*"/', $text) === 1;
    }

    /**
     * @param  callable(list<array{key: ?string, value: ?string, lines: list<string>}>): array<int, array{key: ?string, value: ?string, lines: list<string>}>  $edit
     */
    private function change(callable $edit): void
    {
        $handle = @fopen($this->path, 'c+');

        if ($handle === false) {
            throw CouldNotWriteEnvironmentFile::notWritable();
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw CouldNotWriteEnvironmentFile::notWritable();
            }

            $contents = stream_get_contents($handle);

            if ($contents === false) {
                throw new RuntimeException('Could not read the environment file.');
            }

            $this->backup($contents);

            $lines = Collection::make($edit($this->segments($contents)))
                ->flatMap(fn (array $segment): array => $segment['lines'])
                ->all();

            $newline = str_contains($contents, "\r\n") ? "\r\n" : "\n";
            $updated = implode($newline, $lines);

            if ($updated !== '' && ! str_ends_with($updated, $newline)) {
                $updated .= $newline;
            }

            if (! ftruncate($handle, 0) || ! rewind($handle) || fwrite($handle, $updated) === false) {
                throw CouldNotWriteEnvironmentFile::notWritable();
            }

            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function read(): string
    {
        if (! is_file($this->path)) {
            return '';
        }

        return (string) file_get_contents($this->path);
    }

    /**
     * Splits the file into variables and everything between them. A variable
     * whose double-quoted value runs over several lines is one segment.
     *
     * @return list<array{key: ?string, value: ?string, lines: list<string>}>
     */
    private function segments(string $contents): array
    {
        if ($contents === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\n|\r/', $contents) ?: [];

        // A trailing newline is not a blank line of its own.
        if (end($lines) === '') {
            array_pop($lines);
        }

        $segments = [];

        for ($index = 0, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];

            if (preg_match(self::KEY_PATTERN, $line, $matches) !== 1) {
                $segments[] = ['key' => null, 'value' => null, 'lines' => [$line]];

                continue;
            }

            $block = [$line];
            $raw = $matches[2];

            if (str_starts_with($raw, '"') && ! self::closesDoubleQuote(mb_substr($raw, 1))) {
                while (++$index < $count) {
                    $block[] = $lines[$index];
                    $raw .= "\n".$lines[$index];

                    if (self::closesDoubleQuote($lines[$index])) {
                        break;
                    }
                }
            }

            $segments[] = ['key' => $matches[1], 'value' => self::decode($raw), 'lines' => $block];
        }

        return $segments;
    }

    private function backup(string $contents): void
    {
        File::ensureDirectoryExists($this->backupDirectory, 0700);

        $path = $this->backupDirectory.'/.env.'.CarbonImmutable::now()->format('Ymd-His-u');

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Could not back up the environment file.');
        }

        chmod($path, 0600);

        $backups = File::glob($this->backupDirectory.'/.env.*');
        rsort($backups);

        File::delete(array_slice($backups, $this->backupsKept));
    }
}
