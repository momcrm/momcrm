/**
 * The portal's sections, each shown when the team's contact access rules let
 * the signed-in contact see at least some of it.
 */
export type PortalSection =
    | 'tickets'
    | 'invoices'
    | 'receipts'
    | 'statement'
    | 'services'
    | 'assets'
    | 'articles';

/** What every customer portal page knows about who is signed in. */
export type PortalShared = {
    contact: { id: string; name: string; email: string | null };
    customer: { id: string; name: string };
    /** The provider whose portal this is. */
    team: { name: string };
    sections: Record<PortalSection, boolean>;
    /** Whether their email is a portal contact at other customers too. */
    canSwitch: boolean;
};

/** One customer someone can sign in to the portal as. */
export type PortalChoice = {
    id: string;
    name: string;
    customer: string;
    team: string;
};

/** A ticket's status as the customer sees it. */
export type PortalTicketStatus = {
    name: string;
    hue: number | null;
    is_closed: boolean;
};

/** One of the customer's tickets in the portal. */
export type PortalTicket = {
    id: string;
    number: number;
    title: string;
    status: PortalTicketStatus;
    /** Who raised it, when it came from the customer. */
    raised_by: string | null;
    created_at: string | null;
    updated_at: string | null;
};

/** A message in a ticket's conversation with the customer. */
export type PortalTicketMessage = {
    id: string;
    body: string;
    author_name: string | null;
    /** Written by someone at the customer, by email or in the portal. */
    is_customer: boolean;
    created_at: string | null;
};

/** A file shared with the customer. */
export type PortalFile = {
    id: string;
    name: string;
    size: number;
    mime_type: string;
    created_at: string | null;
    url: string;
};
