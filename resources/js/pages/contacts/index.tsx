import { Head } from '@inertiajs/react';
import ContactTable from '@/components/contacts/contact-table';
import { useCreateContact } from '@/components/contacts/create-contact-dialog';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/contacts';
import type { ContactListItem, Paginated } from '@/types';

export default function ContactsIndex({
    contacts,
    filters,
}: {
    contacts: Paginated<ContactListItem>;
    filters: { search: string | null };
}) {
    const isFiltered = Boolean(filters.search);
    const { openCreateContact } = useCreateContact();

    return (
        <>
            <Head title="Contacts" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Contacts"
                    description="The people you deal with at each customer."
                    actions={
                        <Button onClick={() => openCreateContact()}>
                            Add contact
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
                                <Button onClick={() => openCreateContact()}>
                                    Add contact
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
