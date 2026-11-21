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
    /** Whether their email can also sign in as staff, or at other customers. */
    canSwitch: boolean;
    /** A staff member is looking at the portal as them, read-only. */
    viewingAs: boolean;
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

/** One of the customer's invoices in the portal. */
export type PortalInvoice = {
    id: string;
    reference: string;
    issued_on: string | null;
    due_on: string | null;
    status: 'sent' | 'paid';
    is_overdue: boolean;
    currency: string;
    total_in_cents: number;
    balance_in_cents: number;
    pdf_url: string;
};

/** One of the customer's payments in the portal. */
export type PortalReceipt = {
    id: string;
    reference: string;
    paid_on: string | null;
    method: string;
    currency: string;
    amount_in_cents: number;
    pdf_url: string;
};

/** One of the customer's services in the portal. */
export type PortalService = {
    id: string;
    name: string | null;
    short_name: string | null;
    status: string;
    is_active: boolean;
    quantity: number | null;
    currency: string;
    rate_name: string | null;
    /** What it costs each billing period. */
    rental_in_cents: number | null;
    billing_period_in_months: number | null;
    billing_starts_on: string | null;
    ends_on: string | null;
    location_name: string | null;
};

/** One of the customer's assets in the portal. */
export type PortalAsset = {
    id: string;
    name: string | null;
    serial_number: string | null;
    status: string;
    is_active: boolean;
    location_name: string | null;
};

/** A knowledge-base article in the portal list. */
export type PortalArticleItem = {
    id: string;
    title: string;
    summary: string | null;
    /** Written for this customer alone. */
    is_for_customer: boolean;
    updated_at: string | null;
};
