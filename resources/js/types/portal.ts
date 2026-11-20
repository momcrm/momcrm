/**
 * The portal's sections, each shown when the team's contact access rules let
 * the signed-in contact see at least some of it.
 */
export type PortalSection =
    | 'tickets'
    | 'invoices'
    | 'receipts'
    | 'statement'
    | 'services'
    | 'assets'
    | 'articles';

/** What every customer portal page knows about who is signed in. */
export type PortalShared = {
    contact: { id: string; name: string; email: string | null };
    customer: { id: string; name: string };
    /** The provider whose portal this is. */
    team: { name: string };
    sections: Record<PortalSection, boolean>;
    /** Whether their email is a portal contact at other customers too. */
    canSwitch: boolean;
};

/** One customer someone can sign in to the portal as. */
export type PortalChoice = {
    id: string;
    name: string;
    customer: string;
    team: string;
};
