import type { ReactNode } from 'react';

/**
 * The shared header of every onboarding screen: a progress bar, then the
 * question or title and its helper text.
 */
export default function OnboardingStep({
    step,
    totalSteps,
    title,
    description,
    children,
}: {
    step: number;
    totalSteps: number;
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <p className="text-xs font-medium text-muted-foreground">
                    Step {step} of {totalSteps}
                </p>
                <div
                    className="h-1 w-full overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    aria-valuemin={1}
                    aria-valuemax={totalSteps}
                    aria-valuenow={step}
                >
                    <div
                        className="h-full rounded-full bg-primary transition-all"
                        style={{ width: `${(step / totalSteps) * 100}%` }}
                    />
                </div>
            </div>

            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-muted-foreground">{description}</p>
                )}
            </div>

            {children}
        </div>
    );
}
