export type ActivityItem = {
    id: string;
    type: string;
    label: string;
    details: Record<string, unknown> | null;
    actor: string | null;
    ip_address: string | null;
    created_at: string | null;
};

export type AuditChange = {
    field: string;
    old: string | null;
    new: string | null;
};

export type AuditLogItem = {
    id: string;
    event: 'created' | 'updated' | 'deleted' | 'viewed';
    subject: string;
    changes: AuditChange[];
    actor: string | null;
    ip_address: string | null;
    created_at: string | null;
};

/**
 * Laravel's LengthAwarePaginator as serialized to JSON.
 */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
