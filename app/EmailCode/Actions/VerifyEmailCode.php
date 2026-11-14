<?php

declare(strict_types=1);

namespace App\EmailCode\Actions;

use App\EmailCode\EmailCodeCheckEnum;
use App\EmailCode\EmailCodeEloquentModel;
use App\EmailCode\EmailCodePurposeEnum;
use App\EmailCode\Properties\Attempts;
use App\EmailCode\Properties\ConsumedAt;
use App\EmailCode\Properties\Email;
use App\EmailCode\Properties\Purpose;
use Carbon\CarbonImmutable;

/**
 * Checks a typed code against the latest one emailed to the address. A right
 * code works once; five wrong tries use the code up.
 */
final readonly class VerifyEmailCode
{
    public const int MAX_ATTEMPTS = 5;

    public function __construct(
        private string $email,
        private EmailCodePurposeEnum $purpose,
        private string $code,
    ) {}

    public function __invoke(): EmailCodeCheckEnum
    {
        $emailCode = EmailCodeEloquentModel::query()
            ->where(Email::getDatabaseTableColumnName(), $this->email)
            ->where(Purpose::getDatabaseTableColumnName(), $this->purpose->value)
            ->whereNull(ConsumedAt::getDatabaseTableColumnName())
            ->latest()
            ->first();

        if ($emailCode === null || $emailCode->expires_at?->isPast() !== false) {
            return EmailCodeCheckEnum::Expired;
        }

        if ($emailCode->attempts >= self::MAX_ATTEMPTS) {
            return EmailCodeCheckEnum::TooManyAttempts;
        }

        if (! hash_equals($emailCode->code_hash, self::hash($this->code))) {
            $emailCode->increment(Attempts::getDatabaseTableColumnName());

            return $emailCode->attempts >= self::MAX_ATTEMPTS ? EmailCodeCheckEnum::TooManyAttempts : EmailCodeCheckEnum::Invalid;
        }

        $emailCode->update([ConsumedAt::getDatabaseTableColumnName() => CarbonImmutable::now()]);

        return EmailCodeCheckEnum::Valid;
    }

    public static function check(string $email, EmailCodePurposeEnum $purpose, string $code): EmailCodeCheckEnum
    {
        return new self(mb_strtolower($email), $purpose, trim($code))->handle();
    }

    public function handle(): EmailCodeCheckEnum
    {
        return $this->__invoke();
    }

    public function execute(): EmailCodeCheckEnum
    {
        return $this->__invoke();
    }

    /**
     * Codes are stored hashed with the app key, so a database leak does not reveal them.
     */
    public static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
