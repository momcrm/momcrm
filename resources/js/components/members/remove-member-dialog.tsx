import { Form } from '@inertiajs/react';
import DeleteTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/DeleteTeamInvitationController';
import RemoveMemberController from '@/actions/App/TeamUser/Controllers/RemoveMemberController';
import type { MemberTarget } from '@/components/members/member-meta';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';

function removalDescription(target: MemberTarget): string {
    const openTickets = target.open_tickets ?? 0;
    const unassigned =
        openTickets > 0
            ? ` Their ${openTickets} open ${openTickets === 1 ? 'ticket' : 'tickets'} will become unassigned.`
            : '';

    return `They’ll lose access to the team straight away and come off its groups and projects.${unassigned} Their closed tickets and comments stay.`;
}

/**
 * Asks before removing someone from the team, or revoking an invitation.
 */
export default function RemoveMemberDialog({
    target,
    teamName,
    onClose,
}: {
    target: MemberTarget | null;
    teamName: string;
    onClose: () => void;
}) {
    return (
        <Dialog
            open={target !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogTitle>
                    {target?.pending
                        ? 'Revoke invitation?'
                        : `Remove ${target?.name ?? ''} from ${teamName}?`}
                </DialogTitle>
                <DialogDescription>
                    {target &&
                        (target.pending
                            ? `The invitation link sent to ${target.email} will stop working.`
                            : removalDescription(target))}
                </DialogDescription>
                {target && (
                    <Form
                        {...(target.pending
                            ? DeleteTeamInvitationController.form(target.id)
                            : RemoveMemberController.form(target.id))}
                        options={{ preserveScroll: true }}
                        onSuccess={onClose}
                    >
                        {({ processing }) => (
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {target.pending ? 'Revoke' : 'Remove'}
                                </Button>
                            </DialogFooter>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}
