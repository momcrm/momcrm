import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Eye, Send } from 'lucide-react';
import LinkedRecords from '@/components/custom-fields/linked-records';
import ConfirmDelete from '@/components/confirm-delete';
import ContactForm from '@/components/contacts/contact-form';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { timeAgo } from '@/lib/format';
import DeleteContactController from '@/actions/App/Contact/Controllers/DeleteContactController';
import UpdateContactController from '@/actions/App/Contact/Controllers/UpdateContactController';
import { edit, index, portalInvitation, viewInPortal } from '@/routes/contacts';
import { show as showCustomer } from '@/routes/customers';
import type {
    Abilities,
    Contact,
    CustomerOption,
    LinkedRecordGroup,
} from '@/types';

export default function EditContact({
    can,
    canViewInPortal,
    contact,
    customers,
    linkedRecords,
}: {
    can: Abilities;
    /** Whether they may open the portal as this contact (owners and admins). */
    canViewInPortal: boolean;
    contact: Contact;
    customers: CustomerOption[];
    /** What points at the contact through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: contact.full_name, href: edit(contact.id) },
        ],
    });

    return (
        <>
            <Head title={`Edit ${contact.full_name}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={`Edit ${contact.full_name}`}
                    actions={
                        <>
                            {can.update && contact.email && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    title={
                                        contact.portal_invited_at
                                            ? `Invited ${timeAgo(contact.portal_invited_at)}`
                                            : undefined
                                    }
                                    onClick={() =>
                                        router.post(
                                            portalInvitation.url(contact.id),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <Send />
                                    {contact.portal_invited_at
                                        ? 'Invite to portal again'
                                        : 'Invite to portal'}
                                </Button>
                            )}
                            {canViewInPortal && contact.portal_access && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.post(
                                            viewInPortal.url(contact.id),
                                        )
                                    }
                                >
                                    <Eye />
                                    View as contact
                                </Button>
                            )}
                            {can.delete && (
                                <ConfirmDelete
                                    action={DeleteContactController.form(
                                        contact.id,
                                    )}
                                    title={`Delete ${contact.full_name}?`}
                                    description="This can't be undone."
                                />
                            )}
                        </>
                    }
                />
                {contact.portal_access && (
                    <p className="-mt-3 text-sm text-muted-foreground">
                        Can sign in to the customer portal
                        {contact.portal_last_seen_at
                            ? ` · last there ${timeAgo(contact.portal_last_seen_at)}`
                            : ' · hasn’t signed in yet'}
                    </p>
                )}
                <ContactForm
                    action={UpdateContactController.form(contact.id)}
                    contact={contact}
                    customers={customers}
                    submitLabel="Save"
                    cancelHref={showCustomer(contact.customer_id).url}
                />
                <div className="max-w-2xl">
                    <LinkedRecords groups={linkedRecords} />
                </div>
            </div>
        </>
    );
}
