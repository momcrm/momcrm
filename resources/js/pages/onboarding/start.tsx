import { Form, Head } from '@inertiajs/react';
import OnboardingStep from '@/components/onboarding/onboarding-step';
import OptionCard from '@/components/onboarding/option-card';
import {
    onboardingSteps,
    onboardingTotalSteps,
} from '@/components/onboarding/steps';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import CompleteOnboardingController from '@/actions/App/Onboarding/Controllers/CompleteOnboardingController';

export default function Start() {
    return (
        <>
            <Head title="Pick a starting point" />

            <OnboardingStep
                step={onboardingSteps.start}
                totalSteps={onboardingTotalSteps}
                title="How do you want to start?"
                description="You can switch any time. Nothing here is permanent."
            >
                <Form
                    {...CompleteOnboardingController.form()}
                    className="flex flex-col gap-6"
                >
                    {({ processing }) => (
                        <>
                            <OptionCard
                                label="Start from scratch"
                                description="An empty workspace. The getting-started checklist will guide you."
                                selected
                                onSelect={() => {}}
                            />

                            <div className="flex justify-end">
                                <Button
                                    disabled={processing}
                                    data-test="onboarding-complete-button"
                                >
                                    {processing && <Spinner />}
                                    Start blank
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </OnboardingStep>
        </>
    );
}
