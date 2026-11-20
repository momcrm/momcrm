import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export type Step = { title: string; description: string };

/**
 * The steps of a billing page, as Amphora's billing shows them: numbered,
 * each with a title and what it does, done ones ticked.
 */
export default function Stepper({
    steps,
    current,
}: {
    steps: Step[];
    /** The step shown, counted from 1. */
    current: number;
}) {
    return (
        <ol className="mb-6 flex flex-wrap gap-x-6 gap-y-3">
            {steps.map((step, index) => {
                const number = index + 1;
                const isDone = number < current;
                const isCurrent = number === current;

                return (
                    <li
                        key={step.title}
                        className="flex min-w-0 items-center gap-2.5"
                        aria-current={isCurrent ? 'step' : undefined}
                    >
                        <span
                            className={cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-medium',
                                isDone &&
                                    'border-primary bg-primary text-primary-foreground',
                                isCurrent && 'border-primary text-primary',
                                !isDone &&
                                    !isCurrent &&
                                    'text-muted-foreground',
                            )}
                        >
                            {isDone ? <Check className="size-3.5" /> : number}
                        </span>
                        <span className="min-w-0">
                            <span
                                className={cn(
                                    'block text-sm font-medium',
                                    !isCurrent &&
                                        !isDone &&
                                        'text-muted-foreground',
                                )}
                            >
                                {step.title}
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                {step.description}
                            </span>
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
