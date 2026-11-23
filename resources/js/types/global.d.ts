import type { Auth } from '@/types/auth';
import type { GettingStarted } from '@/types/onboarding';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            hasSampleData?: boolean;
            canRemoveSampleData?: boolean;
            canManageAccessRules?: boolean;
            canManageAttendance?: boolean;
            canManageBankFeeds?: boolean;
            canManageBilling?: boolean;
            canManageConnectors?: boolean;
            canManageCustomFields?: boolean;
            canManageCustomObjects?: boolean;
            canManageEmail?: boolean;
            canManageEnvironment?: boolean;
            canManageRates?: boolean;
            canManageWorkflow?: boolean;
            sidebarCounts?: {
                openTickets: number;
                myOpenTickets: number;
                unreadNotifications: number;
                projects: import('./crm').SidebarProject[];
                groups: {
                    id: string;
                    name: string;
                    hue: number | null;
                    icon: string | null;
                }[];
                objects: import('./crm').CustomObject[];
            } | null;
            /** Whether the signed-in staff member's email can also sign in to a customer portal. */
            canOpenPortal?: boolean;
            gettingStarted?: GettingStarted | null;
            choices?: Record<
                import('./crm').ChoiceList,
                import('./crm').Choice[]
            > | null;
            customFields?: Record<
                import('./crm').CustomFieldTarget,
                import('./crm').CustomField[]
            > | null;
            /** Whether the signed-in member is clocked in, for the header clock. */
            attendanceClock?: import('./attendance').AttendanceClock | null;
            /** The signed-in contact, on customer portal pages only. */
            portal?: import('./portal').PortalShared | null;
            [key: string]: unknown;
        };
    }
}
