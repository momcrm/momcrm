import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { edit } from '@/routes/contacts';
import type { Contact } from '@/types';

export default function ContactList({ contacts }: { contacts: Contact[] }) {
    return (
        <ul className="divide-y rounded-lg border">
            {contacts.map((contact) => (
                <li key={contact.id}>
                    <Link
                        href={edit(contact.id)}
                        className="flex items-center justify-between gap-4 px-4 py-3 hover:bg-accent"
                    >
                        <div className="min-w-0">
                            <p className="truncate font-medium">
                                {contact.full_name}
                                {contact.position && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        · {contact.position}
                                    </span>
                                )}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {[
                                    contact.email,
                                    contact.work_phone,
                                    contact.mobile_phone,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        {contact.is_primary && (
                            <Badge variant="secondary">Primary</Badge>
                        )}
                    </Link>
                </li>
            ))}
        </ul>
    );
}
