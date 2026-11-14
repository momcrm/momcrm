<?php

declare(strict_types=1);

namespace App\Shared;

enum Table: string
{
    case Activities = 'activities';
    case Assets = 'assets';
    case Attachments = 'attachments';
    case AuditLogs = 'audit_logs';
    case Comments = 'comments';
    case Contacts = 'contacts';
    case Customers = 'customers';
    case EmailCodes = 'email_codes';
    case Favorites = 'favorites';
    case GroupUser = 'group_user';
    case Groups = 'groups';
    case InvoiceItems = 'invoice_items';
    case Invoices = 'invoices';
    case LabelTicket = 'label_ticket';
    case Labels = 'labels';
    case Locations = 'locations';
    case Milestones = 'milestones';
    case Notifications = 'notifications';
    case Passkeys = 'passkeys';
    case ProjectUser = 'project_user';
    case Projects = 'projects';
    case Services = 'services';
    case SocialAccounts = 'social_accounts';
    case TeamInvitations = 'team_invitations';
    case TeamUser = 'team_user';
    case Teams = 'teams';
    case Tickets = 'tickets';
    case Users = 'users';
}
