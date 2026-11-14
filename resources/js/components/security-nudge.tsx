import { Link } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { edit as editSecurity } from '@/routes/security';

const storageKey = 'momcrm.security-nudge-dismissed';

function isDismissed(): boolean {
    try {
        return window.localStorage.getItem(storageKey) === '1';
    } catch {
        return false;
    }
}

/**
 * Suggests a passkey to people with neither a passkey nor two-factor
 * authentication. It never blocks anything and stays away once dismissed.
 */
export default function SecurityNudge() {
    const [dismissed, setDismissed] = useState(isDismissed);

    if (dismissed) {
        return null;
    }

    const dismiss = () => {
        try {
            window.localStorage.setItem(storageKey, '1');
        } catch {
            // Storage can be unavailable (private windows); hiding for this visit is enough.
        }

        setDismissed(true);
    };

    return (
        <section className="flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4">
            <div className="flex items-start gap-3">
                <KeyRound className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                <div className="space-y-1">
                    <h2 className="font-medium">Sign in without a password</h2>
                    <p className="text-sm text-muted-foreground">
                        Add a passkey and sign in with your face, fingerprint or
                        device PIN. It's faster than a password and can't be
                        phished.
                    </p>
                </div>
            </div>
            <div className="flex gap-2">
                <Button variant="ghost" size="sm" onClick={dismiss}>
                    Not now
                </Button>
                <Button size="sm" asChild>
                    <Link href={editSecurity()}>Add a passkey</Link>
                </Button>
            </div>
        </section>
    );
}
