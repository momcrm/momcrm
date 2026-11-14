import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import OnboardingStep from '@/components/onboarding/onboarding-step';
import OptionCard from '@/components/onboarding/option-card';
import {
    onboardingSteps,
    onboardingTotalSteps,
} from '@/components/onboarding/steps';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import UpdateAboutController from '@/actions/App/Onboarding/Controllers/UpdateAboutController';

type QuestionKey =
    | 'onboarding_use_case'
    | 'onboarding_role'
    | 'onboarding_team_size';

type Option = { value: string; label: string };

type Props = {
    answers: Record<QuestionKey, string | null>;
    options: Record<QuestionKey, Option[]>;
};

const questions: {
    key: QuestionKey;
    step: number;
    title: string;
    description: string;
}[] = [
    {
        key: 'onboarding_use_case',
        step: onboardingSteps.useCase,
        title: 'What brings you to MomCRM?',
        description: "We'll set things up to match.",
    },
    {
        key: 'onboarding_role',
        step: onboardingSteps.role,
        title: "What's your role?",
        description: 'So we can show you the right things first.',
    },
    {
        key: 'onboarding_team_size',
        step: onboardingSteps.teamSize,
        title: 'How many people are on your team?',
        description: 'Just you is fine too.',
    },
];

export default function About({ answers: savedAnswers, options }: Props) {
    const [index, setIndex] = useState(0);
    const [answers, setAnswers] = useState(savedAnswers);
    const [processing, setProcessing] = useState(false);

    const question = questions[index];
    const isLast = index === questions.length - 1;

    const next = (nextAnswers: Record<QuestionKey, string | null>) => {
        if (!isLast) {
            setIndex(index + 1);

            return;
        }

        router.put(UpdateAboutController.url(), nextAnswers, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    const skip = () => {
        const nextAnswers = { ...answers, [question.key]: null };

        setAnswers(nextAnswers);
        next(nextAnswers);
    };

    return (
        <>
            <Head title="Tell us about you" />

            <OnboardingStep
                step={question.step}
                totalSteps={onboardingTotalSteps}
                title={question.title}
                description={question.description}
            >
                <div className="flex flex-col gap-3">
                    {options[question.key].map((option) => (
                        <OptionCard
                            key={option.value}
                            label={option.label}
                            selected={answers[question.key] === option.value}
                            onSelect={() =>
                                setAnswers({
                                    ...answers,
                                    [question.key]: option.value,
                                })
                            }
                        />
                    ))}
                </div>

                <div className="flex items-center justify-between">
                    {index > 0 ? (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setIndex(index - 1)}
                        >
                            Back
                        </Button>
                    ) : (
                        <span />
                    )}

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={skip}
                            disabled={processing}
                        >
                            Skip
                        </Button>
                        <Button
                            type="button"
                            onClick={() => next(answers)}
                            disabled={
                                processing || answers[question.key] === null
                            }
                            data-test="onboarding-continue-button"
                        >
                            {processing && <Spinner />}
                            Continue
                        </Button>
                    </div>
                </div>
            </OnboardingStep>
        </>
    );
}
