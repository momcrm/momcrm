import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import { toast } from 'sonner';
import { visitWithQuery } from '@/lib/query';
import { toggle as toggleFavorite } from '@/routes/favorites';
import { archive, move, patch } from '@/routes/tickets';
import { store as storeSubtask } from '@/routes/tickets/subtasks';

/**
 * The props a page hosting ticket views reloads after a change.
 */
const RELOAD = ['tickets', 'drawer', 'favoriteTicketIds', 'sidebarCounts'];

const showFirstError = (errors: Record<string, string>) => {
    const message = Object.values(errors)[0];

    if (message) {
        toast.error(message);
    }
};

/**
 * Opening the drawer and changing tickets in place from any ticket view.
 */
export function useTicketActions() {
    const openTicket = useCallback((id: string) => {
        visitWithQuery({ ticket: id }, { only: ['drawer'], keepPage: true });
    }, []);

    const closeTicket = useCallback(() => {
        visitWithQuery({ ticket: null }, { only: ['drawer'], keepPage: true });
    }, []);

    const updateTicket = useCallback(
        (id: string, fields: Record<string, unknown>) => {
            router.patch(patch.url(id), fields as never, {
                only: RELOAD,
                preserveScroll: true,
                preserveState: true,
                onError: showFirstError,
            });
        },
        [],
    );

    const moveTicket = useCallback(
        (id: string, statusId: string, position: number) => {
            router.put(
                move.url(id),
                { status_id: statusId, position },
                {
                    only: RELOAD,
                    preserveScroll: true,
                    preserveState: true,
                    onError: showFirstError,
                },
            );
        },
        [],
    );

    const archiveTicket = useCallback((id: string) => {
        router.post(
            archive.url(id),
            {},
            { only: RELOAD, preserveScroll: true, preserveState: true },
        );
    }, []);

    const addSubtask = useCallback((parentId: string, title: string) => {
        router.post(
            storeSubtask.url(parentId),
            { title },
            {
                only: RELOAD,
                preserveScroll: true,
                preserveState: true,
                onError: showFirstError,
            },
        );
    }, []);

    const toggleStar = useCallback((id: string) => {
        router.post(
            toggleFavorite.url(),
            { favoritable_type: 'ticket', favoritable_id: id },
            { only: RELOAD, preserveScroll: true, preserveState: true },
        );
    }, []);

    return {
        openTicket,
        closeTicket,
        updateTicket,
        moveTicket,
        archiveTicket,
        addSubtask,
        toggleStar,
    };
}
