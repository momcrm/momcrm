export type GettingStartedStep = {
    key: 'customer' | 'contact' | 'ticket' | 'invite' | 'passkey';
    title: string;
    description: string;
    action: string;
    href: string;
    done: boolean;
};

export type GettingStarted = {
    steps: GettingStartedStep[];
    completed: number;
    total: number;
};
