import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { CommandPaletteProvider } from '@/components/command-palette';
import SampleDataBanner from '@/components/sample-data-banner';
import SuspendedTeamBanner from '@/components/suspended-team-banner';
import { CreateContactProvider } from '@/components/contacts/create-contact-dialog';
import { CreateCustomerProvider } from '@/components/customers/create-customer-dialog';
import { CreateInvoiceProvider } from '@/components/invoices/create-invoice-dialog';
import { CreateTicketProvider } from '@/components/tickets/create-ticket-dialog';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <CreateCustomerProvider>
                <CreateContactProvider>
                    <CreateTicketProvider>
                        <CreateInvoiceProvider>
                            <CommandPaletteProvider>
                                <AppSidebar />
                                <AppContent
                                    variant="sidebar"
                                    className="min-w-0 overflow-x-clip"
                                >
                                    <AppSidebarHeader
                                        breadcrumbs={breadcrumbs}
                                    />
                                    <SuspendedTeamBanner />
                                    <SampleDataBanner />
                                    {children}
                                </AppContent>
                            </CommandPaletteProvider>
                        </CreateInvoiceProvider>
                    </CreateTicketProvider>
                </CreateContactProvider>
            </CreateCustomerProvider>
        </AppShell>
    );
}
