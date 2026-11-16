import { useFlashToast } from '@/hooks/use-flash-toast';
import { useAppearance } from '@/hooks/use-appearance';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            toastOptions={{
                className: 'shadow-pop',
                style: {
                    minHeight: 40,
                    padding: '8px 12px',
                    gap: 10,
                    fontSize: '12.5px',
                },
            }}
            position="bottom-right"
            style={
                {
                    '--normal-bg': 'var(--foreground)',
                    '--normal-text': 'var(--background)',
                    '--normal-border': 'transparent',
                    '--border-radius': '8px',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
