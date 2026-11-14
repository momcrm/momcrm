/**
 * Where each onboarding screen sits in the progress bar.
 */
export const onboardingSteps = {
    useCase: 1,
    role: 2,
    teamSize: 3,
    workspace: 4,
    invite: 5,
    start: 6,
} as const;

export const onboardingTotalSteps = 6;
