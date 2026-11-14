import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import OnboardingStep from '@/components/onboarding/onboarding-step';
import {
    onboardingSteps,
    onboardingTotalSteps,
} from '@/components/onboarding/steps';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import UpdateWorkspaceController from '@/actions/App/Onboarding/Controllers/UpdateWorkspaceController';
import type { Team } from '@/types';

export default function Workspace({ team }: { team: Team }) {
    return (
        <>
            <Head title="Create your workspace" />

            <OnboardingStep
                step={onboardingSteps.workspace}
                totalSteps={onboardingTotalSteps}
                title="Create your workspace"
                description="This is where your team's customers, contacts and tickets live."
            >
                <Form
                    {...UpdateWorkspaceController.form()}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Workspace name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={team.name}
                                    required
                                    autoFocus
                                    autoComplete="organization"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Most people use their business name. You can
                                    change it later.
                                </p>
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex justify-end">
                                <Button
                                    disabled={processing}
                                    data-test="onboarding-workspace-button"
                                >
                                    {processing && <Spinner />}
                                    Continue
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </OnboardingStep>
        </>
    );
}
