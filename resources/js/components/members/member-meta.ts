export type Role = 'owner' | 'admin' | 'member';

export const roleLabels: Record<Role, string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

/** Activity within this many minutes counts as being online. */
const onlineWithinMinutes = 5;

export function isOnline(lastActiveAt: string | null): boolean {
    return (
        lastActiveAt !== null &&
        Date.now() - new Date(lastActiveAt).getTime() <
            onlineWithinMinutes * 60_000
    );
}

/**
 * Someone the members menu and the remove dialog act on: a person in the
 * team, or a pending invitation (whose id is the invitation's).
 */
export type MemberTarget = {
    id: string;
    name: string;
    email: string;
    role: Role;
    pending: boolean;
    is_you: boolean;
    can_remove: boolean;
    open_tickets: number | null;
};
