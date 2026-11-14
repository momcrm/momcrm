export type Option = { value: string; label: string };

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
