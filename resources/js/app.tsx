import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { initializePreferences } from '@/hooks/use-preferences';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import CustomerLayout from '@/layouts/customer/layout';
import OnboardingLayout from '@/layouts/onboarding-layout';
import ServiceLayout from '@/layouts/service/layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name, page) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name === 'error':
                return (page.props as { auth?: { user?: unknown } }).auth?.user
                    ? AppLayout
                    : null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('onboarding/'):
                return OnboardingLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('customers/workspace/'):
                return [AppLayout, CustomerLayout];
            case name.startsWith('services/workspace/'):
                return [AppLayout, ServiceLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4b5bd6',
    },
});

// This will set light / dark mode on load...
initializeTheme();
initializePreferences();
