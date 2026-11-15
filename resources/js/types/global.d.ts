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
            } | null;
            gettingStarted?: GettingStarted | null;
            choices?: Record<
                import('./crm').ChoiceList,
                import('./crm').Choice[]
            > | null;
            [key: string]: unknown;
        };
    }
}
