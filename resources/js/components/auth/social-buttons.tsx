import { GoogleMark, MicrosoftMark } from '@/components/brand-marks';
import type { Option } from '@/types';

const marks: Record<
    string,
    (props: { className?: string }) => React.JSX.Element
> = {
    google: GoogleMark,
    microsoft: MicrosoftMark,
};

/**
 * "Or continue with" Google / Microsoft. Only providers with keys configured
 * are offered. These are full page loads: the provider's page is not an Inertia page.
 */
export default function SocialButtons({
    providers,
    hrefFor = (provider) => `/auth/${provider}/redirect`,
}: {
    providers: Option[];
    /** Where each button goes; the customer portal has its own. */
    hrefFor?: (provider: string) => string;
}) {
    if (providers.length === 0) {
        return null;
    }

    return (
        <>
            <div className="flex items-center gap-3 text-xs text-muted-foreground">
                <span className="h-px flex-1 bg-border" />
                or continue with
                <span className="h-px flex-1 bg-border" />
            </div>
            <div className="grid grid-flow-col gap-2">
                {providers.map((provider) => {
                    const Mark = marks[provider.value];

                    return (
                        <a
                            key={provider.value}
                            href={hrefFor(provider.value)}
                            className="flex flex-col items-center gap-1.5 rounded-lg border px-3 py-3 text-sm font-medium transition-colors hover:bg-accent"
                        >
                            {Mark && <Mark />}
                            {provider.label}
                        </a>
                    );
                })}
            </div>
        </>
    );
}
