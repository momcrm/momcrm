<?php

declare(strict_types=1);

namespace App\EmailCode\Actions;

use App\EmailCode\EmailCode;
use App\EmailCode\EmailCodeEloquentModel;
use App\EmailCode\EmailCodePurposeEnum;
use App\EmailCode\Notifications\EmailCodeNotification;
use App\EmailCode\Properties\ConsumedAt;
use App\EmailCode\Properties\Email;
use App\EmailCode\Properties\Id;
use App\EmailCode\Properties\Purpose;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Emails a new 6-digit code to an address. Earlier unused codes for the same
 * address and purpose stop working, so only the latest email counts.
 */
final readonly class CreateEmailCode
{
    public const int MINUTES_VALID = 10;

    public function __construct(
        private string $email,
        private EmailCodePurposeEnum $purpose,
    ) {}

    public function __invoke(): void
    {
        EmailCodeEloquentModel::query()
            ->where(Email::getDatabaseTableColumnName(), $this->email)
            ->where(Purpose::getDatabaseTableColumnName(), $this->purpose->value)
            ->whereNull(ConsumedAt::getDatabaseTableColumnName())
            ->update([ConsumedAt::getDatabaseTableColumnName() => CarbonImmutable::now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $emailCode = EmailCode::new()
            ->setId(Id::generate())
            ->setEmail($this->email)
            ->setPurpose($this->purpose)
            ->setCodeHash(VerifyEmailCode::hash($code))
            ->setAttempts(0)
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(self::MINUTES_VALID));

        EmailCodeEloquentModel::query()->create($emailCode->forDatabaseCreate());

        Notification::route('mail', $this->email)->notify(new EmailCodeNotification($code, $this->purpose));
    }

    public static function send(string $email, EmailCodePurposeEnum $purpose): void
    {
        new self(mb_strtolower($email), $purpose)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
