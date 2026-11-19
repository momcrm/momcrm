<?php

declare(strict_types=1);

namespace App\Shared;

enum Table: string
{
    case AccessRules = 'access_rules';
    case Activities = 'activities';
    case Assets = 'assets';
    case Attachments = 'attachments';
    case AuditLogs = 'audit_logs';
    case Choices = 'choices';
    case Comments = 'comments';
    case Connectors = 'connectors';
    case Contacts = 'contacts';
    case Contracts = 'contracts';
    case CustomFields = 'custom_fields';
    case CustomObjects = 'custom_objects';
    case Customers = 'customers';
    case EmailCodes = 'email_codes';
    case Favorites = 'favorites';
    case Folders = 'folders';
    case GroupUser = 'group_user';
    case Groups = 'groups';
    case InvoiceItems = 'invoice_items';
    case Invoices = 'invoices';
    case LabelAsset = 'label_asset';
    case LabelProject = 'label_project';
    case LabelTicket = 'label_ticket';
    case Locations = 'locations';
    case MailboxMessages = 'mailbox_messages';
    case Mailboxes = 'mailboxes';
    case Milestones = 'milestones';
    case Notifications = 'notifications';
    case Passkeys = 'passkeys';
    case ProjectUser = 'project_user';
    case Projects = 'projects';
    case Rates = 'rates';
    case Records = 'records';
    case Secrets = 'secrets';
    case Services = 'services';
    case SocialAccounts = 'social_accounts';
    case TeamInvitations = 'team_invitations';
    case TeamUser = 'team_user';
    case Teams = 'teams';
    case Tickets = 'tickets';
    case Users = 'users';
}
