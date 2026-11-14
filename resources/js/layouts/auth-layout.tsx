import AuthBackdropLayout from '@/layouts/auth/auth-backdrop-layout';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <AuthBackdropLayout title={title} description={description}>
            {children}
        </AuthBackdropLayout>
    );
}
