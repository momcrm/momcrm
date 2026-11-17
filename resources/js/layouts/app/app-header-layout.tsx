import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { CreateCustomerProvider } from '@/components/customers/create-customer-dialog';
import { CreateTicketProvider } from '@/components/tickets/create-ticket-dialog';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <CreateCustomerProvider>
                <CreateTicketProvider>
                    <AppHeader breadcrumbs={breadcrumbs} />
                    <AppContent variant="header">{children}</AppContent>
                </CreateTicketProvider>
            </CreateCustomerProvider>
        </AppShell>
    );
}
