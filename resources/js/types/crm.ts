export type Option = { value: string; label: string };

/**
 * What the signed-in person may do to a record under the team's access rules,
 * keyed by camel-cased action (update, delete, archive, changeVisibility, ...).
 */
export type Abilities = Partial<Record<string, boolean>>;

export type CustomerOption = { id: string; name: string };

export type Customer = {
    id: string;
    number: number;
    name: string;
    /** Their business or tax registration number. */
    tax_id: string | null;
    /** What their country calls it, e.g. ABN, VAT or GST No. */
    tax_id_label: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    status: 'active' | 'inactive';
    billing_type: CustomerBillingType;
    /** Their own days to pay; null when they go by the team's. */
    days_to_pay: number | null;
    /** ISO 4217 code the customer is billed in, e.g. AUD. */
    currency: string;
    prefix: string | null;
    type_id: string | null;
    notes: string | null;
    is_sample: boolean;
    custom_fields: CustomFieldValues;
};

export type CustomerBillingType = 'automatic' | 'manual' | 'excluded';

/**
 * The header and nav every tab of a customer's workspace shares.
 */
export type CustomerWorkspace = {
    customer: Customer;
    /** Names of the records the customer's record links point at, by id. */
    custom_field_labels: Record<string, string>;
    can: Abilities;
    counts: {
        tickets: number;
        projects: number;
        contacts: number;
        locations: number;
        assets: number;
        services: number;
        invoices: number;
        payments: number;
        files: number;
        secrets: number;
        contracts: number;
    };
};

export type ContractType = 'msa' | 'nda' | 'sla' | 'sa';

export type ContractStatus =
    | 'draft'
    | 'pending'
    | 'active'
    | 'expired'
    | 'cancelled';

export type ContractItem = {
    id: string;
    customer_id: string;
    title: string;
    type: ContractType;
    status: ContractStatus;
    signed_on: string | null;
    starts_on: string | null;
    ends_on: string | null;
    purchase_order: string | null;
    description: string | null;
    notes: string | null;
    custom_fields: CustomFieldValues;
};

/** A customer's secret, without its values. */
export type SecretItem = {
    id: string;
    customer_id: string;
    asset_id: string | null;
    title: string;
    description: string | null;
    version: number;
    updated_at: string | null;
};

export type SecretPair = { key: string; value: string };

/** A folder of a customer's files; top-level folders have no parent. */
export type FolderItem = { id: string; parent_id: string | null; name: string };

/** What a customer owes on sent invoices, in cents. */
export type CustomerMoney = {
    balance_in_cents: number;
    current_in_cents: number;
    overdue_in_cents: number;
    /** What they have paid that isn't applied to an invoice. */
    credit_in_cents: number;
};

export type CustomerListItem = Customer & {
    /** Names of the records the customer's record links point at, by id. */
    custom_field_labels?: Record<string, string>;
    contacts_count: number;
    services_count: number;
    balance_in_cents: number;
    credit_in_cents: number;
    updated_at: string | null;
};

export type Contact = {
    id: string;
    customer_id: string;
    first_name: string;
    last_name: string | null;
    full_name: string;
    email: string | null;
    position: string | null;
    work_phone: string | null;
    mobile_phone: string | null;
    is_primary: boolean;
    receives_invoices: boolean;
    custom_fields: CustomFieldValues;
};

export type ContactListItem = Contact & { customer_name: string | null };

export type Location = {
    id: string;
    customer_id: string;
    name: string;
    street_one: string | null;
    street_two: string | null;
    suburb: string | null;
    state: string | null;
    postcode: string | null;
    country: string | null;
    is_primary: boolean;
    custom_fields: CustomFieldValues;
};

export type Asset = {
    id: string;
    customer_id: string;
    name: string;
    serial_number: string | null;
    location_id: string | null;
    status: 'active' | 'retired';
    description: string | null;
    /** Sent with the customer's assets and the edit form. */
    labels?: LabelTag[];
    custom_fields: CustomFieldValues;
};

/** What one unit of a rate costs a month in one currency. */
export type RatePrice = {
    currency: string;
    price_in_cents: number;
    cost_in_cents: number | null;
};

/**
 * One entry in the team's price list, priced per unit per month in one or
 * more currencies.
 */
export type Rate = {
    id: string;
    /** The rate this one sits under, if any; parents have prices too. */
    parent_id: string | null;
    name: string;
    description: string | null;
    prices: RatePrice[];
    billing_period_in_months: number;
    prorates_first_month: boolean;
    is_archived: boolean;
    /** How many rates it sits under. Lists come in tree order. */
    depth: number;
    /** Its name after its parents', e.g. "Microsoft 365 › Business Standard". */
    path: string;
};

export type ServiceLifecycle = 'active' | 'inactive' | 'archived';

export type Service = {
    id: string;
    customer_id: string;
    name: string;
    short_name: string | null;
    status: 'active' | 'inactive';
    lifecycle: ServiceLifecycle;
    auto_renew: boolean;
    quantity: number | null;
    /** The currency its customer is billed in, which every amount is in. */
    currency: string;
    rate_id: string | null;
    rate:
        | (Pick<
              Rate,
              'id' | 'name' | 'billing_period_in_months' | 'is_archived'
          > & {
              /** The monthly price in the service's currency; null when the rate has none in it. */
              price_in_cents: number | null;
          })
        | null;
    /** The service's own billing period; null follows the rate's. */
    billing_period_in_months: number | null;
    effective_billing_period_in_months: number | null;
    /** One unit for a whole billing period, from the rate. */
    unit_price_in_cents: number | null;
    /** The whole service for a billing period, before tax. */
    rental_in_cents: number | null;
    /** The period it is billed for next, when it is billed at all. */
    next_invoice: {
        starts_on: string;
        ends_on: string;
        unit_price_in_cents: number;
        total_in_cents: number;
    } | null;
    billing_starts_on: string | null;
    ends_on: string | null;
    location_id: string | null;
    contact_id: string | null;
    note: string | null;
    archived_at: string | null;
    custom_fields: CustomFieldValues;
};

/**
 * A row of the services list: the service with its customer's and
 * location's names.
 */
export type ServiceListItem = Service & {
    customer_name: string | null;
    location_name: string | null;
    updated_at: string | null;
};

/**
 * A service opened on its own page: the record and what every section shares.
 */
export type ServiceWorkspace = {
    service: Service;
    customer: CustomerOption;
    location: CustomerOption | null;
    contact: CustomerOption | null;
    can: Abilities;
    counts: { linked: number; invoices: number };
};

/**
 * An invoice line that billed a service, with the invoice it is on.
 */
export type ServiceInvoiceLine = {
    id: string;
    description: string;
    quantity: number;
    unit_price_in_cents: number;
    line_total_in_cents: number;
    invoice: {
        id: string;
        number: number;
        reference: string;
        status: string;
        issued_on: string | null;
    };
};

/**
 * What the service form offers: the currency the customer is billed in, the
 * team's rates priced in it, the customer's locations and contacts, and the
 * billing periods.
 */
export type ServiceFormOptions = {
    currency: string;
    rates: Rate[];
    locations: CustomerOption[];
    contacts: CustomerOption[];
    statuses: Option[];
    billingPeriods: number[];
};

/**
 * The lists a team can customise, as keyed in the shared `choices` prop.
 */
export type ChoiceList =
    | 'customer_type'
    | 'label'
    | 'ticket_priority'
    | 'ticket_status'
    | 'ticket_type';

/**
 * One entry in a team's customisable list, such as the priority "Urgent".
 */
export type Choice = {
    id: string;
    name: string;
    hue: number | null;
    icon: string | null;
    category: string | null;
    position: number;
    is_default: boolean;
    /** No longer offered, but kept by the records that still use it. */
    is_archived: boolean;
};

/**
 * A kind of record a team has defined for itself, such as "Vehicles".
 */
export type CustomObject = {
    id: string;
    /** What one record is called, such as "Vehicle". */
    name: string;
    name_plural: string;
    description: string | null;
    icon: string | null;
    hue: number | null;
    position: number;
    /** Out of the sidebar, but its records are kept. */
    is_archived: boolean;
};

/**
 * One record of a custom object.
 */
export type RecordItem = {
    id: string;
    object_id: string;
    /** Counted from 1 within its object. */
    number: number;
    name: string;
    custom_fields: CustomFieldValues;
    /** Names of the records the record's links point at, by id (in lists). */
    custom_field_labels?: Record<string, string>;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * The kinds of record a team can add its own fields to.
 */
export type CustomFieldTarget =
    | 'ticket'
    | 'customer'
    | 'contact'
    | 'project'
    | 'asset'
    | 'location'
    | 'service'
    | 'contract'
    | 'invoice'
    | 'milestone'
    /** Records of a custom object; the field's `object_id` says which. */
    | 'record';

export type CustomFieldType =
    | 'text'
    | 'number'
    | 'date'
    | 'checkbox'
    | 'select'
    | 'multi_select'
    | 'relation';

/**
 * The kinds of record a record-link field can point at.
 */
export type CustomFieldRelation =
    | 'member'
    | 'customer'
    | 'contact'
    | 'ticket'
    | 'project'
    | 'asset'
    | 'location'
    | 'service'
    | 'contract'
    | 'invoice'
    | 'milestone'
    | 'record';

/**
 * The records that link to one record through one record-link field.
 */
export type LinkedRecordGroup = {
    /** The linking field's id. */
    key: string;
    /** The kind of record the field is on, such as "Tickets". */
    source: string;
    /** The linking field's name. */
    field: string;
    /** How many records link; `records` holds only the newest few. */
    count: number;
    records: { id: string; title: string; url: string }[];
};

/**
 * One thing a select field offers. Records store its id.
 */
export type CustomFieldOption = {
    id: string;
    name: string;
    hue: number | null;
};

/**
 * A field a team has added to one kind of record.
 */
export type CustomField = {
    id: string;
    target: CustomFieldTarget;
    /** The custom object the field is on; null for a built-in kind of record. */
    object_id: string | null;
    type: CustomFieldType;
    /** What a record link points at; null for every other type. */
    relation: CustomFieldRelation | null;
    /** The custom object a link to a custom object's records points at. */
    relation_object_id: string | null;
    name: string;
    description: string | null;
    /** Empty unless the field is a select. */
    options: CustomFieldOption[];
    position: number;
    /** No longer offered, but its values are kept. */
    is_archived: boolean;
};

/**
 * What a record holds for one custom field: text, a number, a date
 * (YYYY-MM-DD), a ticked checkbox, an option's id, several options' ids, or
 * the id of a linked record.
 */
export type CustomFieldValue = string | number | boolean | string[] | null;

/** A record's custom field values by field id. A field without a value has no key. */
export type CustomFieldValues = Record<string, CustomFieldValue>;

export type Ticket = {
    id: string;
    number: number;
    title: string;
    description: string | null;
    status_id: string | null;
    priority_id: string | null;
    type_id: string | null;
    group_id: string | null;
    customer_id: string | null;
    project_id: string | null;
    parent_id: string | null;
    contact_id: string | null;
    location_id: string | null;
    assignee_id: string | null;
    starts_on: string | null;
    due_on: string | null;
    estimate_minutes: number | null;
    position: number;
    closed_at: string | null;
    archived_at: string | null;
    created_at: string | null;
    custom_fields: CustomFieldValues;
};

export type TicketListItem = Ticket & {
    /** Names of the records the ticket's record links point at, by id. */
    custom_field_labels?: Record<string, string>;
    customer_name: string | null;
    assignee_name: string | null;
    project_name: string | null;
    project_hue: number | null;
    labels: LabelTag[];
    subtasks_count: number;
    closed_subtasks_count: number;
};

export type LabelTag = { id: string; name: string; hue: number | null };

export type TicketDrawerData = Omit<
    TicketListItem,
    'subtasks_count' | 'closed_subtasks_count'
> & {
    /** Names of the records the ticket's record links point at, by id. */
    custom_field_labels: Record<string, string>;
    /** What points at the ticket through record-link custom fields. */
    linked_records: LinkedRecordGroup[];
    parent: { id: string; number: number; title: string } | null;
    subtasks: {
        id: string;
        number: number;
        title: string;
        status_id: string | null;
    }[];
    is_favorite: boolean;
    contact: {
        name: string;
        email: string | null;
        phone: string | null;
    } | null;
    reply: TicketReply;
    /** Who wrote in, for a ticket opened by an email. */
    requester: {
        name: string | null;
        email: string;
    } | null;
    location_name: string | null;
    can: Abilities;
    attachment_can: Abilities;
    comments: TicketComment[];
    attachments: AttachmentItem[];
    activity: import('./activity').ActivityItem[];
};

/**
 * Who a reply to the customer goes to and which mailbox sends it; either is
 * missing when the ticket has no address or the team no connected mailbox.
 */
export type TicketReply = {
    to: string | null;
    from: string | null;
};

export type TicketComment = {
    id: string;
    body: string;
    author_id: string | null;
    /** The team member who wrote it, or the person whose email it is. */
    author_name: string | null;
    /** Set when the comment arrived by email. */
    sender_email: string | null;
    /** An internal note, or part of the conversation with the customer. */
    visibility: 'internal' | 'public';
    /** How a reply emailed to the customer fared. */
    delivery: {
        status: 'pending' | 'sent' | 'failed';
        error: string | null;
    } | null;
    mentions: string[];
    created_at: string | null;
    can_delete: boolean;
};

export type AttachmentItem = {
    id: string;
    name: string;
    description?: string | null;
    folder_id?: string | null;
    size: number;
    mime_type: string;
    uploaded_by: string | null;
    created_at: string | null;
    url: string;
};

export type InboxKind = 'mention' | 'assignment' | 'comment' | 'update';

export type NotificationItem = {
    id: string;
    kind: InboxKind;
    team_id: string;
    actor_id: string | null;
    actor_name: string | null;
    subject_type: 'ticket' | 'project' | 'record';
    subject_id: string;
    number: number;
    title: string;
    project_name: string | null;
    message: string;
    excerpt: string | null;
    read_at: string | null;
    created_at: string | null;
    url: string;
};

export type TicketPickerOptions = {
    members: CustomerOption[];
    projects: ProjectOption[];
    customers: CustomerOption[];
    groups: GroupOption[];
};

/**
 * One of the team's groups, as offered in pickers.
 */
export type GroupOption = {
    id: string;
    name: string;
    hue: number | null;
    icon: string | null;
    member_ids: string[];
};

export type TicketView = 'list' | 'board' | 'table';

export type TicketFilters = {
    view: TicketView;
    status: 'open' | 'closed' | 'all';
    search: string | null;
    priority: string[];
    assignee: string | null;
    project: string | null;
    label: string | null;
    type: string | null;
    group: string | null;
    sort: string;
    direction: 'asc' | 'desc';
    /** Filters on custom fields, by field id. */
    custom_fields: Record<string, string>;
};

export type ProjectStatus =
    | 'planning'
    | 'in_progress'
    | 'at_risk'
    | 'on_hold'
    | 'completed';

export type Project = {
    id: string;
    number: number;
    name: string;
    description: string | null;
    icon: string | null;
    hue: number | null;
    status: ProjectStatus;
    visibility: 'open' | 'restricted';
    customer_id: string | null;
    lead_id: string | null;
    group_id: string | null;
    starts_on: string | null;
    due_on: string | null;
    archived_at: string | null;
    custom_fields: CustomFieldValues;
};

export type ProjectListItem = Project & {
    customer_name: string | null;
    lead_name: string | null;
    labels: LabelTag[];
    members: CustomerOption[];
    tickets_count: number;
    closed_tickets_count: number;
    overdue_tickets_count: number;
    is_favorite: boolean;
    updated_at: string | null;
};

export type ProjectFormOptions = {
    members: CustomerOption[];
    customers: CustomerOption[];
    groups: (CustomerOption & { hue: number | null; icon: string | null })[];
    statuses: Option[];
    visibilities: Option[];
};

export type Milestone = {
    id: string;
    name: string;
    due_on: string;
    completed_at: string | null;
    custom_fields: CustomFieldValues;
    /** What points at the milestone through record-link custom fields. */
    linked_records: LinkedRecordGroup[];
};

export type SidebarProject = {
    id: string;
    name: string;
    hue: number | null;
    status: ProjectStatus;
    is_favorite: boolean;
};

export type ProjectOption = {
    id: string;
    name: string;
    hue: number | null;
    customer_id: string | null;
};

export type CustomerChildOption = {
    id: string;
    name: string;
    customer_id: string;
};

export type TicketFormOptions = {
    customers: CustomerOption[];
    contacts: CustomerChildOption[];
    locations: CustomerChildOption[];
    members: CustomerOption[];
    projects: ProjectOption[];
    groups: GroupOption[];
};

export type InvoiceStatus = 'draft' | 'sent' | 'paid' | 'void';

export type Invoice = {
    id: string;
    number: number;
    reference: string;
    customer_id: string;
    issued_on: string | null;
    due_on: string | null;
    status: InvoiceStatus;
    /** ISO 4217 code every amount on the invoice is in. */
    currency: string;
    is_overdue: boolean;
    tax_rate_basis_points: number;
    purchase_order: string | null;
    notes: string | null;
    sent_at: string | null;
    /** When it was last emailed to the customer. */
    emailed_at: string | null;
    paid_at: string | null;
    /** The sum of the payments applied to the invoice. */
    paid_in_cents: number;
    custom_fields: CustomFieldValues;
};

export type InvoiceTotals = {
    subtotal_in_cents: number;
    tax_in_cents: number;
    total_in_cents: number;
};

/**
 * Everything printed on an invoice, as the invoice page, the invoice dialog
 * and the PDF show it.
 */
export type InvoiceDocument = {
    /** "Invoice", "Tax invoice" or "VAT invoice". */
    title: string;
    invoice: Invoice;
    items: InvoiceItem[];
    totals: InvoiceTotals;
    customer: Customer;
    /** The team the invoice is from. */
    from: TeamBilling;
    /** What the customer has paid that isn't applied to an invoice. */
    customer_credit_in_cents: number;
    /** The payments applied to the invoice, oldest first. */
    payments: PaymentAllocation[];
    /** How to pay by BPAY, when the team has a biller code. */
    bpay: { biller_code: string; reference: string } | null;
    /** The customer's contacts it can be emailed to. */
    recipients: ContactRecipient[];
};

/** The business details a team prints on its invoices. */
export type TeamBilling = {
    /** The name invoices are issued under: the billing name, or the team's name. */
    name: string;
    billing_name: string | null;
    /** What the team's country calls its tax ID, e.g. ABN, VAT or GST No. */
    tax_id_label: string | null;
    tax_id: string | null;
    billing_email: string | null;
    billing_phone: string | null;
    billing_address: string | null;
    payment_details: string | null;
    bpay_biller_code: string | null;
    /** How many days customers have to pay, unless a customer has their own. */
    days_to_pay: number;
    /** What invoice numbers start with, e.g. INV-; empty for none. */
    invoice_prefix: string;
};

export type InvoiceListItem = Invoice &
    InvoiceTotals & {
        customer_name: string | null;
        /** What is still owed: the total less payments, never below 0. */
        balance_in_cents: number;
    };

export type PaymentMethod =
    | 'bank_transfer'
    | 'bpay'
    | 'card'
    | 'cash'
    | 'cheque'
    | 'credit';

/** A payment as lists and the payment dialog show it. */
export type Payment = {
    id: string;
    number: number;
    /** e.g. PAY-0042 */
    reference_number: string;
    customer_id: string;
    customer_name: string | null;
    paid_on: string | null;
    amount_in_cents: number;
    /** How much of it has been applied to invoices. */
    allocated_in_cents: number;
    unallocated_in_cents: number;
    currency: string;
    method: PaymentMethod;
    method_label: string;
    /** The bank or receipt reference. */
    reference: string | null;
    notes: string | null;
    created_at: string | null;
};

/** Part of a payment applied to one invoice. */
export type PaymentAllocation = {
    id: string;
    payment_id: string;
    invoice_id: string;
    invoice_reference: string | null;
    payment_reference_number: string | null;
    paid_on: string | null;
    method_label: string | null;
    amount_in_cents: number;
};

/** One line of a customer's statement. */
export type StatementEntry = {
    date: string;
    type: 'invoice' | 'payment';
    id: string;
    reference: string;
    description: string;
    debit_in_cents: number;
    credit_in_cents: number;
    /** What they owe after this line; below 0 is credit. */
    balance_in_cents: number;
};

/** A customer's account over a period. */
export type CustomerStatement = {
    customer: { id: string; name: string };
    currency: string;
    from: string;
    to: string;
    opening_balance_in_cents: number;
    entries: StatementEntry[];
    closing_balance_in_cents: number;
    /** What is owing today, by how long past due. */
    ages: { key: string; label: string; amount_in_cents: number }[];
    credit_in_cents: number;
};

/** A customer's contact a document can be emailed to. */
export type ContactRecipient = {
    id: string;
    name: string;
    email: string;
    is_primary: boolean;
    /** Whether invoices go to them unless someone chooses otherwise. */
    receives_invoices: boolean;
};

/** What the payment dialog fetches. */
export type PaymentDetails = {
    payment: Payment;
    allocations: PaymentAllocation[];
    can: Abilities;
    methods: Option[];
    /** Who the receipt can be emailed to. */
    contacts: ContactRecipient[];
    /** The bank feed line it was recorded from, if any. */
    bank_transaction: {
        account: string | null;
        posted_on: string | null;
        description: string;
    } | null;
};

/** What the record payment dialog fetches for a customer. */
export type OutstandingInvoices = {
    customer: { id: string; name: string; currency: string };
    methods: Option[];
    invoices: InvoiceListItem[];
};

export type InvoiceItem = {
    id: string;
    service_id: string | null;
    /** The one-off charge the line bills, if any. */
    charge_id: string | null;
    description: string;
    quantity: number;
    unit_price_in_cents: number;
    line_total_in_cents: number;
    rental_starts_on: string | null;
    rental_ends_on: string | null;
};

export type CalendarWindow = {
    view: 'month' | 'week';
    date: string;
    from: string;
    to: string;
};

export type TimelineWindow = {
    zoom: 'weeks' | 'months';
    from: string;
    to: string;
};

export type TimelineProject = {
    id: string;
    name: string;
    hue: number | null;
    icon: string | null;
    starts_on: string | null;
    due_on: string | null;
};

export type TimelineMilestone = {
    id: string;
    project_id: string;
    name: string;
    due_on: string;
    completed_at: string | null;
};

export type AccessCondition = {
    attribute: string;
    operator: string;
    value: string | string[] | null;
};

/** An outside account the team has connected, such as a Microsoft 365 sign-in. */
export type Connector = {
    id: string;
    /** The key of its connector type, such as "microsoft". */
    type: string;
    type_label: string;
    name: string;
    account: string;
    status: 'connected' | 'needs_reconnect';
    /** What the connector can be used for, such as "Email". */
    capabilities: string[];
    /** What was chosen at sign-in, each in a word or two. */
    options: string[];
    last_error: string | null;
    /** Whether it is connected by signing in at the service, not by a form. */
    uses_sign_in: boolean;
    /** What was typed into its form, by field name; never the secrets. */
    settings: Record<string, string | number | null> | null;
    /** The addresses of the mailboxes that go through this connector. */
    mailboxes: string[];
    connected_at: string | null;
    /** The name of whoever connected it, when they are still around. */
    connected_by: string | null;
};

/** A connected account a mailbox can be added through. */
export type MailConnector = {
    id: string;
    name: string;
    account: string;
    type_label: string;
    shared_mailboxes: boolean;
};

/** Something a person may tick before signing in to connect an account. */
export type ConnectorSignInOption = {
    key: string;
    label: string;
    hint: string | null;
};

/** One field of the form a connector type is connected with. */
export type ConnectorField = {
    name: string;
    label: string;
    kind: 'text' | 'email' | 'password' | 'number' | 'select';
    required: boolean;
    placeholder: string | null;
    hint: string | null;
    default: string | number | null;
    options: { value: string; label: string }[];
    /** The titled box it is shown in, with the others of the same group. */
    group: string | null;
    /** How many of a group's six columns it takes. */
    span: number;
};

/** A kind of outside account, as its connector type describes itself. */
export type ConnectorType = {
    value: string;
    label: string;
    /** What it connects, in a line. */
    description: string;
    /** Its logo as an image address; none means a plain icon. */
    logo: string | null;
    capabilities: string[];
    /** Whether this installation is set up to connect it. */
    available: boolean;
    /** How an account is connected: by signing in, or by filling in a form. */
    connection: 'sign_in' | 'form' | null;
    options: ConnectorSignInOption[];
    fields: ConnectorField[];
    /** The form field that says which account it is. */
    account_field: string | null;
    form_introduction: string | null;
};

/** A mailbox whose mail opens tickets, read and sent through a connector. */
export type Mailbox = {
    id: string;
    address: string;
    display_name: string | null;
    is_shared: boolean;
    /** How the connector it goes through is doing. */
    status: 'connected' | 'needs_reconnect';
    connector_id: string;
    connector_name: string;
    connector_type: string | null;
    group_id: string | null;
    auto_create_tickets: boolean;
    last_synced_at: string | null;
    last_error: string | null;
};

export type AccessRuleItem = {
    id: string;
    name: string;
    resources: string[];
    actions: string[];
    effect: 'allow' | 'deny';
    conditions: AccessCondition[];
    is_enabled: boolean;
};

export type AccessRuleOptions = {
    resources: (Option & { actions: string[] })[];
    actions: Option[];
    effects: Option[];
    operators: Option[];
    attributes: (Option & {
        resource: string | null;
        type: 'choice' | 'boolean';
        operators: string[];
        options: Option[];
    })[];
};

/** A bank account whose statements are imported as bank feed lines. */
export type BankAccount = {
    id: string;
    name: string;
    kind: string;
    kind_label: string;
    currency: string;
    last_imported_at: string | null;
};

export type BankTransactionStatus = 'pending' | 'applied' | 'hidden';

/** A deposit imported from a bank statement. */
export type BankTransaction = {
    id: string;
    bank_account_id: string;
    bank_account_name: string | null;
    bank_account_kind: string | null;
    currency: string | null;
    posted_on: string | null;
    amount_in_cents: number;
    description: string;
    /** The BPAY customer reference number it was paid with. */
    crn: string | null;
    payer_name: string | null;
    customer_id: string | null;
    customer_name: string | null;
    matched_by: string | null;
    matched_by_label: string | null;
    payment_id: string | null;
    /** e.g. PAY-0042 */
    payment_reference_number: string | null;
    status: BankTransactionStatus;
    status_label: string;
};

/** Which columns of a CSV statement hold what, counted from 0. */
export type StatementColumns = {
    has_header: boolean;
    date: number;
    description: number[];
    amount: number | null;
    credit: number | null;
    debit: number | null;
    date_order: 'dmy' | 'mdy' | 'ymd' | null;
    layout?: string;
};

/** What the import needs to ask about a CSV it couldn't read on its own. */
export type StatementColumnsRequest = {
    file: string;
    headings: string[];
    rows: string[][];
    guess: StatementColumns;
};

/** A customer to pick for a bank feed line, with the currency they pay in. */
export type BankFeedCustomer = { id: string; name: string; currency: string };

/** A team's rule for bank feed deposits whose description contains its text. */
export type BankRule = {
    id: string;
    kind: 'match' | 'hide';
    contains: string;
    customer_id: string | null;
    customer_name: string | null;
};

/** Something sold once, such as a laptop or a call-out, priced per currency. */
export type Product = {
    id: string;
    name: string;
    description: string | null;
    prices: RatePrice[];
    is_archived: boolean;
};

/** A one-off charge to a customer, waiting for their next invoice or on one. */
export type Charge = {
    id: string;
    customer_id: string;
    product_id: string | null;
    description: string;
    quantity: number;
    unit_price_in_cents: number;
    total_in_cents: number;
    currency: string;
    charged_on: string | null;
    /** The invoice it is on, once billed. */
    invoice: { id: string; reference: string } | null;
};

/** A line a customer is due to be billed: a service period or a one-off charge. */
export type BillingLine = {
    /** Names the line the same way each time, to pick it. */
    key: string;
    kind: 'service' | 'charge';
    description: string;
    quantity: number;
    unit_price_in_cents: number;
    total_in_cents: number;
};

/** What the "Bill now" dialog fetches for a customer. */
export type CustomerBilling = {
    customer: { id: string; name: string; currency: string };
    date: string;
    due_on: string;
    lines: BillingLine[];
};

/** A customer in a billing run, with what they are due. */
export type BillingRunCustomer = {
    id: string;
    name: string;
    currency: string;
    due_on: string;
    total_in_cents: number;
    lines: BillingLine[];
};
