import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import CsvUpload from '@/components/customers/csv-upload';
import OnboardingStep from '@/components/onboarding/onboarding-step';
import OptionCard from '@/components/onboarding/option-card';
import {
    onboardingSteps,
    onboardingTotalSteps,
} from '@/components/onboarding/steps';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import CompleteOnboardingController from '@/actions/App/Onboarding/Controllers/CompleteOnboardingController';
import StartWithImportController from '@/actions/App/Onboarding/Controllers/StartWithImportController';
import StartWithSampleDataController from '@/actions/App/Onboarding/Controllers/StartWithSampleDataController';

type Choice = 'sample' | 'import' | 'blank';

const choices: { value: Choice; label: string; description: string }[] = [
    {
        value: 'sample',
        label: 'Explore with sample data',
        description:
            "We'll add a few example customers, contacts and tickets so you can click around. Remove them in one click.",
    },
    {
        value: 'import',
        label: 'Bring your customers in',
        description:
            "Upload a CSV from your spreadsheet or old CRM. We'll match the columns for you.",
    },
    {
        value: 'blank',
        label: 'Start from scratch',
        description:
            'An empty workspace. The getting-started checklist will guide you.',
    },
];

function SubmitButton({
    action,
    label,
}: {
    action: { action: string; method: 'post' };
    label: string;
}) {
    return (
        <Form {...action} className="flex justify-end">
            {({ processing }) => (
                <Button
                    disabled={processing}
                    data-test="onboarding-complete-button"
                >
                    {processing && <Spinner />}
                    {label}
                </Button>
            )}
        </Form>
    );
}

export default function Start({
    recommended,
    maxRows,
}: {
    recommended: Choice;
    maxRows: number;
}) {
    const [choice, setChoice] = useState<Choice>(recommended);

    return (
        <>
            <Head title="Pick a starting point" />

            <OnboardingStep
                step={onboardingSteps.start}
                totalSteps={onboardingTotalSteps}
                title="How do you want to start?"
                description="You can switch any time. Nothing here is permanent."
            >
                <div className="flex flex-col gap-3">
                    {choices.map((option) => (
                        <OptionCard
                            key={option.value}
                            label={option.label}
                            description={option.description}
                            badge={
                                option.value === recommended
                                    ? 'Recommended'
                                    : undefined
                            }
                            selected={choice === option.value}
                            onSelect={() => setChoice(option.value)}
                        />
                    ))}
                </div>

                {choice === 'sample' && (
                    <SubmitButton
                        action={StartWithSampleDataController.form()}
                        label="Use sample data"
                    />
                )}
                {choice === 'import' && (
                    <CsvUpload
                        action={StartWithImportController.form()}
                        maxRows={maxRows}
                    />
                )}
                {choice === 'blank' && (
                    <SubmitButton
                        action={CompleteOnboardingController.form()}
                        label="Start blank"
                    />
                )}
            </OnboardingStep>
        </>
    );
}
