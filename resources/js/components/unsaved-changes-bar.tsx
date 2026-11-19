import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

/**
 * How many fields differ from what was last saved. A field holding a map or
 * a list is compared by its contents.
 */
export function countChanges<T extends object>(data: T, saved: T): number {
    return (Object.keys(data) as (keyof T)[]).filter(
        (field) =>
            data[field] !== saved[field] &&
            JSON.stringify(data[field]) !== JSON.stringify(saved[field]),
    ).length;
}

/**
 * Asks before leaving a page with unsaved changes: in a dialog when following
 * a link, and with the browser's own prompt when closing or reloading the tab.
 */
function useLeaveGuard(isOn: boolean) {
    const [pendingUrl, setPendingUrl] = useState<string | null>(null);
    const isLeaving = useRef(false);

    useEffect(() => {
        if (!isOn) {
            return;
        }

        const onBeforeUnload = (event: BeforeUnloadEvent) =>
            event.preventDefault();

        window.addEventListener('beforeunload', onBeforeUnload);

        const removeBefore = router.on('before', (event) => {
            const { visit } = event.detail;

            if (
                isLeaving.current ||
                visit.prefetch ||
                visit.method !== 'get' ||
                visit.url.pathname === window.location.pathname
            ) {
                return;
            }

            event.preventDefault();
            setPendingUrl(visit.url.href);
        });

        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            removeBefore();
        };
    }, [isOn]);

    const leave = () => {
        if (!pendingUrl) {
            return;
        }

        isLeaving.current = true;
        router.visit(pendingUrl, {
            onFinish: () => (isLeaving.current = false),
        });
        setPendingUrl(null);
    };

    return { pendingUrl, stay: () => setPendingUrl(null), leave };
}

/**
 * A dark pill fixed to the foot of the window while a form has unsaved
 * changes, saying how many and offering to reset or save them. It takes no
 * room in the page, and it sits inside the form, so its Save button
 * submits it.
 */
export default function UnsavedChangesBar({
    count,
    processing,
    onReset,
}: {
    count: number;
    processing: boolean;
    onReset: () => void;
}) {
    const guard = useLeaveGuard(count > 0);

    return (
        <>
            <div
                role="status"
                aria-live="polite"
                className="pointer-events-none fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 justify-center"
            >
                {count > 0 && (
                    <div className="pointer-events-auto flex animate-in items-center gap-6 rounded-xl bg-foreground py-2 pr-2 pl-4 text-sm text-background shadow-pop duration-200 fade-in slide-in-from-bottom-2">
                        <span className="flex items-center gap-2 whitespace-nowrap">
                            <span className="size-1.5 rounded-full bg-warning" />
                            <span className="tabular-nums">
                                {count === 1
                                    ? '1 unsaved change'
                                    : `${count} unsaved changes`}
                            </span>
                        </span>
                        <span className="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                disabled={processing}
                                onClick={onReset}
                                className="border border-background/15 bg-background/10 text-background hover:bg-background/20 hover:text-background"
                            >
                                Reset
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                                data-test="save-changes-button"
                            >
                                {processing && <Spinner />}
                                Save
                            </Button>
                        </span>
                    </div>
                )}
            </div>

            <Dialog
                open={guard.pendingUrl !== null}
                onOpenChange={(isOpen) => !isOpen && guard.stay()}
            >
                <DialogContent>
                    <DialogTitle>Leave without saving?</DialogTitle>
                    <DialogDescription>
                        {count === 1
                            ? 'Your change on this page will be lost.'
                            : `Your ${count} changes on this page will be lost.`}
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Keep editing
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            type="button"
                            onClick={guard.leave}
                        >
                            Discard and leave
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
