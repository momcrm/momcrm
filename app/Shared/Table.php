<?php

declare(strict_types=1);

namespace App\Shared;

enum Table: string
{
    case AccessRules = 'access_rules';
    case Activities = 'activities';
    case ArticleCategories = 'article_categories';
    case ArticleLinks = 'article_links';
    case ArticleVersions = 'article_versions';
    case Articles = 'articles';
    case Assets = 'assets';
    case Attachments = 'attachments';
    case AuditLogs = 'audit_logs';
    case BankAccounts = 'bank_accounts';
    case BankRules = 'bank_rules';
    case BankTransactions = 'bank_transactions';
    case Charges = 'charges';
    case Choices = 'choices';
    case Comments = 'comments';
    case Connectors = 'connectors';
    case Contacts = 'contacts';
    case ConversationParticipants = 'conversation_participants';
    case Conversations = 'conversations';
    case Contracts = 'contracts';
    case CustomFields = 'custom_fields';
    case CustomObjects = 'custom_objects';
    case Customers = 'customers';
    case EmailCodes = 'email_codes';
    case Favorites = 'favorites';
    case Folders = 'folders';
    case GroupUser = 'group_user';
    case Groups = 'groups';
    case Holidays = 'holidays';
    case InvoiceItems = 'invoice_items';
    case LabelArticle = 'label_article';
    case Invoices = 'invoices';
    case LeaveRequests = 'leave_requests';
    case LabelAsset = 'label_asset';
    case LabelProject = 'label_project';
    case LabelTicket = 'label_ticket';
    case Locations = 'locations';
    case MailboxMessages = 'mailbox_messages';
    case Mailboxes = 'mailboxes';
    case Messages = 'messages';
    case Milestones = 'milestones';
    case Notifications = 'notifications';
    case Passkeys = 'passkeys';
    case PaymentAllocations = 'payment_allocations';
    case Payments = 'payments';
    case ProjectUser = 'project_user';
    case Products = 'products';
    case Projects = 'projects';
    case Rates = 'rates';
    case Records = 'records';
    case Secrets = 'secrets';
    case Services = 'services';
    case ShiftBreaks = 'shift_breaks';
    case Shifts = 'shifts';
    case SocialAccounts = 'social_accounts';
    case TeamInvitations = 'team_invitations';
    case TeamUser = 'team_user';
    case Teams = 'teams';
    case Tickets = 'tickets';
    case Timesheets = 'timesheets';
    case Users = 'users';
    case WorkSchedules = 'work_schedules';
}
