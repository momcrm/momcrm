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
    abn: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    status: 'active' | 'inactive';
    billing_type: CustomerBillingType;
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
};

export type CustomerListItem = Customer & {
    /** Names of the records the customer's record links point at, by id. */
    custom_field_labels?: Record<string, string>;
    contacts_count: number;
    services_count: number;
    balance_in_cents: number;
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

export type Service = {
    id: string;
    customer_id: string;
    name: string;
    status: 'active' | 'pending' | 'cancelled';
    quantity: number | null;
    unit_price_in_cents: number | null;
    billing_period_in_months: number | null;
    starts_on: string | null;
    ends_on: string | null;
    location_id: string | null;
    note: string | null;
    custom_fields: CustomFieldValues;
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
    location_name: string | null;
    can: Abilities;
    attachment_can: Abilities;
    comments: TicketComment[];
    attachments: AttachmentItem[];
    activity: import('./activity').ActivityItem[];
};

export type TicketComment = {
    id: string;
    body: string;
    author_id: string | null;
    author_name: string | null;
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
    is_overdue: boolean;
    tax_rate_basis_points: number;
    purchase_order: string | null;
    notes: string | null;
    sent_at: string | null;
    paid_at: string | null;
    custom_fields: CustomFieldValues;
};

export type InvoiceTotals = {
    subtotal_in_cents: number;
    tax_in_cents: number;
    total_in_cents: number;
};

export type InvoiceListItem = Invoice &
    InvoiceTotals & { customer_name: string | null };

export type InvoiceItem = {
    id: string;
    service_id: string | null;
    description: string;
    quantity: number;
    unit_price_in_cents: number;
    line_total_in_cents: number;
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

/** A Microsoft 365 mailbox the team has connected. */
export type Mailbox = {
    id: string;
    address: string;
    display_name: string | null;
    is_shared: boolean;
    status: 'connected' | 'needs_reconnect';
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
