import { Link, router } from '@inertiajs/react';
import {
    Mail,
    MoreHorizontal,
    Send,
    Shield,
    Plus,
    Ticket,
    User,
    UserMinus,
} from 'lucide-react';
import { toast } from 'sonner';
import ResendTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/ResendTeamInvitationController';
import { roleLabels } from '@/components/members/member-meta';
import type { MemberTarget, Role } from '@/components/members/member-meta';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { show as showMember, update as updateRole } from '@/routes/members';
import { index as ticketsIndex } from '@/routes/tickets';

const assignableRoles: Role[] = ['admin', 'member'];

/**
 * The "…" menu for someone in the team or a pending invitation: their
 * profile and tickets, assigning them a ticket, copying their email,
 * changing their role, resending an invite, and removing them.
 */
export default function MemberActionsMenu({
    target,
    canChangeRoles,
    canInvite,
    showProfileLink = true,
    onAssign,
    onRemove,
    triggerClassName,
}: {
    target: MemberTarget;
    canChangeRoles: boolean;
    canInvite: boolean;
    /** Leave out "View profile" on the profile page itself. */
    showProfileLink?: boolean;
    onAssign: (target: MemberTarget) => void;
    onRemove: (target: MemberTarget) => void;
    triggerClassName?: string;
}) {
    const canChangeThisRole =
        canChangeRoles &&
        !target.pending &&
        !target.is_you &&
        target.role !== 'owner';

    const copyEmail = () => {
        void navigator.clipboard
            .writeText(target.email)
            .then(() => toast.success('Email copied.'));
    };

    const changeRole = (role: string) =>
        router.patch(
            updateRole.url(target.id),
            { role },
            { preserveScroll: true },
        );

    const resendInvite = () =>
        router.post(
            ResendTeamInvitationController.url(target.id),
            {},
            { preserveScroll: true },
        );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                aria-label={`More for ${target.name}`}
                className={cn(
                    'flex size-7 items-center justify-center rounded-md text-soft-foreground outline-none hover:bg-accent hover:text-foreground data-[state=open]:bg-accent',
                    triggerClassName,
                )}
            >
                <MoreHorizontal className="size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                {!target.pending && (
                    <>
                        {showProfileLink && (
                            <DropdownMenuItem asChild>
                                <Link href={showMember(target.id)}>
                                    <User />
                                    View profile
                                </Link>
                            </DropdownMenuItem>
                        )}
                        <DropdownMenuItem asChild>
                            <Link
                                href={ticketsIndex({
                                    query: { assignee: target.id },
                                })}
                            >
                                <Ticket />
                                View their tickets
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem onSelect={() => onAssign(target)}>
                            <Plus />
                            Assign a ticket
                        </DropdownMenuItem>
                    </>
                )}
                <DropdownMenuItem onSelect={copyEmail}>
                    <Mail />
                    Copy email
                </DropdownMenuItem>
                {canChangeThisRole && (
                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger>
                            <Shield />
                            Change role
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent>
                            <DropdownMenuRadioGroup
                                value={target.role}
                                onValueChange={changeRole}
                            >
                                {assignableRoles.map((role) => (
                                    <DropdownMenuRadioItem
                                        key={role}
                                        value={role}
                                    >
                                        {roleLabels[role]}
                                    </DropdownMenuRadioItem>
                                ))}
                            </DropdownMenuRadioGroup>
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>
                )}
                {target.pending && canInvite && (
                    <DropdownMenuItem onSelect={resendInvite}>
                        <Send />
                        Resend invite
                    </DropdownMenuItem>
                )}
                {target.can_remove && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => onRemove(target)}
                        >
                            <UserMinus />
                            {target.pending
                                ? 'Revoke invitation'
                                : 'Remove from team'}
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
