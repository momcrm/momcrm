<?php

declare(strict_types=1);

namespace App\Shared;

use App\AccessRule\AccessRuleEloquentModel;
use App\Activity\ActivityEloquentModel;
use App\Article\ArticleEloquentModel;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\Asset\AssetEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use App\BankAccount\BankAccountEloquentModel;
use App\BankRule\BankRuleEloquentModel;
use App\BankTransaction\BankTransactionEloquentModel;
use App\Charge\ChargeEloquentModel;
use App\Choice\ChoiceEloquentModel;
use App\Comment\CommentEloquentModel;
use App\Connector\ConnectorEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomObject\CustomObjectEloquentModel;
use App\EmailCode\EmailCodeEloquentModel;
use App\Favorite\FavoriteEloquentModel;
use App\Folder\FolderEloquentModel;
use App\Group\GroupEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Location\LocationEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Passkey\PasskeyEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Product\ProductEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Rate\RateEloquentModel;
use App\Record\RecordEloquentModel;
use App\Secret\SecretEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Shared\Traits\Enum;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\SocialAccount\SocialAccountEloquentModel;
use App\Team\TeamEloquentModel;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Model;

/**
 * The morph map: one alias per Eloquent model, enforced in AppServiceProvider.
 *
 * The values are stored in polymorphic columns (audit_logs.auditable_type,
 * activities.subject_type), so never change an existing value. Case names
 * may be renamed freely. Every new EloquentModel needs a case here.
 */
enum MorphMapKeyEnum: string
{
    use Enum;

    case AccessRule = 'access_rule';
    case Activity = 'activity';
    case Article = 'article';
    case ArticleCategory = 'article_category';
    case ArticleLink = 'article_link';
    case ArticleVersion = 'article_version';
    case Asset = 'asset';
    case Attachment = 'attachment';
    case AuditLog = 'audit_log';
    case BankAccount = 'bank_account';
    case BankRule = 'bank_rule';
    case BankTransaction = 'bank_transaction';
    case Charge = 'charge';
    case Choice = 'choice';
    case Comment = 'comment';
    case Connector = 'connector';
    case Contact = 'contact';
    case Contract = 'contract';
    case Customer = 'customer';
    case CustomField = 'custom_field';
    case CustomObject = 'custom_object';
    case EmailCode = 'email_code';
    case Favorite = 'favorite';
    case Folder = 'folder';
    case Group = 'group';
    case Invoice = 'invoice';
    case InvoiceItem = 'invoice_item';
    case Location = 'location';
    case Mailbox = 'mailbox';
    case MailboxMessage = 'mailbox_message';
    case Milestone = 'milestone';
    case Passkey = 'passkey';
    case Payment = 'payment';
    case PaymentAllocation = 'payment_allocation';
    case Product = 'product';
    case Project = 'project';
    case Rate = 'rate';
    case Record = 'record';
    case Secret = 'secret';
    case Service = 'service';
    case Shift = 'shift';
    case ShiftBreak = 'shift_break';
    case SocialAccount = 'social_account';
    case Team = 'team';
    case TeamInvitation = 'team_invitation';
    case TeamUser = 'team_user';
    case Ticket = 'ticket';
    case User = 'user';

    /**
     * The alias => model class map for Relation::enforceMorphMap().
     *
     * @return array<string, class-string<Model>>
     */
    public static function map(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $key): array => [$key->value => $key->modelClass()])
            ->all();
    }

    /**
     * The alias of a model, as stored in polymorphic columns.
     */
    public static function fromModel(Model $model): self
    {
        return self::from($model->getMorphClass());
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::AccessRule => AccessRuleEloquentModel::class,
            self::Activity => ActivityEloquentModel::class,
            self::Article => ArticleEloquentModel::class,
            self::ArticleCategory => ArticleCategoryEloquentModel::class,
            self::ArticleLink => ArticleLinkEloquentModel::class,
            self::ArticleVersion => ArticleVersionEloquentModel::class,
            self::Asset => AssetEloquentModel::class,
            self::Attachment => AttachmentEloquentModel::class,
            self::AuditLog => AuditLogEloquentModel::class,
            self::BankAccount => BankAccountEloquentModel::class,
            self::BankRule => BankRuleEloquentModel::class,
            self::BankTransaction => BankTransactionEloquentModel::class,
            self::Charge => ChargeEloquentModel::class,
            self::Choice => ChoiceEloquentModel::class,
            self::Comment => CommentEloquentModel::class,
            self::Connector => ConnectorEloquentModel::class,
            self::Contact => ContactEloquentModel::class,
            self::Contract => ContractEloquentModel::class,
            self::Customer => CustomerEloquentModel::class,
            self::CustomField => CustomFieldEloquentModel::class,
            self::CustomObject => CustomObjectEloquentModel::class,
            self::EmailCode => EmailCodeEloquentModel::class,
            self::Favorite => FavoriteEloquentModel::class,
            self::Folder => FolderEloquentModel::class,
            self::Group => GroupEloquentModel::class,
            self::Invoice => InvoiceEloquentModel::class,
            self::InvoiceItem => InvoiceItemEloquentModel::class,
            self::Location => LocationEloquentModel::class,
            self::Mailbox => MailboxEloquentModel::class,
            self::MailboxMessage => MailboxMessageEloquentModel::class,
            self::Milestone => MilestoneEloquentModel::class,
            self::Passkey => PasskeyEloquentModel::class,
            self::Payment => PaymentEloquentModel::class,
            self::PaymentAllocation => PaymentAllocationEloquentModel::class,
            self::Product => ProductEloquentModel::class,
            self::Project => ProjectEloquentModel::class,
            self::Rate => RateEloquentModel::class,
            self::Record => RecordEloquentModel::class,
            self::Secret => SecretEloquentModel::class,
            self::Service => ServiceEloquentModel::class,
            self::Shift => ShiftEloquentModel::class,
            self::ShiftBreak => ShiftBreakEloquentModel::class,
            self::SocialAccount => SocialAccountEloquentModel::class,
            self::Team => TeamEloquentModel::class,
            self::TeamInvitation => TeamInvitationEloquentModel::class,
            self::TeamUser => TeamUserEloquentModel::class,
            self::Ticket => TicketEloquentModel::class,
            self::User => UserEloquentModel::class,
        };
    }
}
