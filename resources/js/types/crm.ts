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
