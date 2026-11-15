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
    notes: string | null;
    is_sample: boolean;
};

export type CustomerListItem = Customer & { contacts_count: number };

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
};

export type Asset = {
    id: string;
    customer_id: string;
    name: string;
    serial_number: string | null;
    location_id: string | null;
    status: 'active' | 'retired';
    description: string | null;
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
};

export type TicketStatus =
    | 'new'
    | 'in_progress'
    | 'waiting_on_customer'
    | 'waiting_on_supplier'
    | 'closed';

export type TicketPriority = 'low' | 'normal' | 'high' | 'urgent';

export type Ticket = {
    id: string;
    number: number;
    title: string;
    description: string | null;
    status: TicketStatus;
    priority: TicketPriority;
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
};

export type TicketListItem = Ticket & {
    customer_name: string | null;
    assignee_name: string | null;
    project_name: string | null;
    project_color: ColorName | null;
    labels: TicketLabel[];
    subtasks_count: number;
    closed_subtasks_count: number;
};

export type TicketLabel = { id: string; name: string; color: ColorName };

export type TicketDrawerData = Omit<
    TicketListItem,
    'subtasks_count' | 'closed_subtasks_count'
> & {
    parent: { id: string; number: number; title: string } | null;
    subtasks: {
        id: string;
        number: number;
        title: string;
        status: TicketStatus;
    }[];
    is_favorite: boolean;
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
    subject_type: 'ticket' | 'project';
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
    labels: TicketLabel[];
    customers: CustomerOption[];
    statuses: Option[];
    priorities: Option[];
};

export type TicketView = 'list' | 'board' | 'table';

export type TicketFilters = {
    view: TicketView;
    status: 'open' | 'closed' | 'all';
    search: string | null;
    priority: TicketPriority[];
    assignee: string | null;
    project: string | null;
    label: string | null;
    sort: string;
    direction: 'asc' | 'desc';
};

export type ColorName =
    | 'amber'
    | 'blue'
    | 'green'
    | 'indigo'
    | 'rose'
    | 'slate'
    | 'teal'
    | 'violet';

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
    color: ColorName;
    status: ProjectStatus;
    visibility: 'open' | 'restricted';
    customer_id: string | null;
    lead_id: string | null;
    group_id: string | null;
    starts_on: string | null;
    due_on: string | null;
    archived_at: string | null;
};

export type ProjectListItem = Project & {
    customer_name: string | null;
    lead_name: string | null;
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
    groups: (CustomerOption & { color: ColorName; icon: string | null })[];
    statuses: Option[];
    visibilities: Option[];
    colors: ColorName[];
};

export type Milestone = {
    id: string;
    name: string;
    due_on: string;
    completed_at: string | null;
};

export type SidebarProject = {
    id: string;
    name: string;
    color: ColorName;
    status: ProjectStatus;
    is_favorite: boolean;
};

export type ProjectOption = {
    id: string;
    name: string;
    color: ColorName;
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
    priorities: Option[];
    statuses: Option[];
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
    mine: boolean;
};

export type TimelineWindow = {
    zoom: 'weeks' | 'months';
    from: string;
    to: string;
};

export type TimelineProject = {
    id: string;
    name: string;
    color: ColorName;
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
