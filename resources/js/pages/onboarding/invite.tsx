import { Head, Link } from '@inertiajs/react';
import InviteForm from '@/components/invite-form';
import OnboardingStep from '@/components/onboarding/onboarding-step';
import {
    onboardingSteps,
    onboardingTotalSteps,
} from '@/components/onboarding/steps';
import { Button } from '@/components/ui/button';
import SendInvitesController from '@/actions/App/Onboarding/Controllers/SendInvitesController';
import { start } from '@/routes/onboarding';

export default function Invite() {
    return (
        <>
            <Head title="Invite your team" />

            <OnboardingStep
                step={onboardingSteps.invite}
                totalSteps={onboardingTotalSteps}
                title="Invite your team"
                description="MomCRM works best when everyone sees the same customers and tickets."
            >
                <InviteForm
                    action={SendInvitesController.form()}
                    secondaryAction={
                        <Button variant="ghost" asChild>
                            <Link href={start()}>I'll do this later</Link>
                        </Button>
                    }
                />
            </OnboardingStep>
        </>
    );
}
