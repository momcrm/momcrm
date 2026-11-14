import { Head, Link } from '@inertiajs/react';
import ContactTable from '@/components/contacts/contact-table';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/contacts';
import type { ContactListItem, Paginated } from '@/types';

export default function ContactsIndex({
    contacts,
    filters,
}: {
    contacts: Paginated<ContactListItem>;
    filters: { search: string | null };
}) {
    const isFiltered = Boolean(filters.search);

    return (
        <>
            <Head title="Contacts" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Contacts"
                    description="The people you deal with at each customer."
                    actions={
                        <Button asChild>
                            <Link href={create()}>Add contact</Link>
                        </Button>
                    }
                />

                {(contacts.total > 0 || isFiltered) && (
                    <SearchInput
                        value={filters.search}
                        placeholder="Search contacts"
                        only={['contacts', 'filters']}
                    />
                )}

                {contacts.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title={`No matches for "${filters.search}"`}
                            description="Check the spelling or try a shorter search."
                        />
                    ) : (
                        <EmptyState
                            title="No contacts yet"
                            description="Add the people you deal with at each customer, so you know who to call."
                            action={
                                <Button asChild>
                                    <Link href={create()}>Add contact</Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <>
                        <ContactTable contacts={contacts.data} />
                        <Pager page={contacts} only="contacts" />
                    </>
                )}
            </div>
        </>
    );
}

ContactsIndex.layout = {
    breadcrumbs: [{ title: 'Contacts', href: index() }],
};
