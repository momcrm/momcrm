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
            sidebarCounts?: {
                openTickets: number;
                myOpenTickets: number;
                projects: import('./crm').SidebarProject[];
            } | null;
            gettingStarted?: GettingStarted | null;
            [key: string]: unknown;
        };
    }
}
