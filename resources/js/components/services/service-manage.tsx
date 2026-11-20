import { Form, router } from '@inertiajs/react';
import { Archive, ArchiveRestore, Power, PowerOff } from 'lucide-react';
import type { ReactNode } from 'react';
import ActivateServiceController from '@/actions/App/Service/Controllers/ActivateServiceController';
import ArchiveServiceController from '@/actions/App/Service/Controllers/ArchiveServiceController';
import ChangeServiceRateController from '@/actions/App/Service/Controllers/ChangeServiceRateController';
import DeactivateServiceController from '@/actions/App/Service/Controllers/DeactivateServiceController';
import DeleteServiceController from '@/actions/App/Service/Controllers/DeleteServiceController';
import RestoreServiceController from '@/actions/App/Service/Controllers/RestoreServiceController';
import SetServiceAutoRenewController from '@/actions/App/Service/Controllers/SetServiceAutoRenewController';
import ConfirmDelete from '@/components/confirm-delete';
import RateCombobox from '@/components/rates/rate-combobox';
import { SettingsRow, SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { formatCents, priceIn } from '@/lib/money';
import type { Abilities, Rate, Service } from '@/types';

const keepScroll = { preserveScroll: true };

/**
 * One action in a settings card: what it does on the left, its control on
 * the right.
 */
function ActionRow({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-4 px-4 py-3.5">
            <div className="min-w-0 flex-1 basis-64">
                <p className="text-sm font-medium">{title}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {description}
                </p>
            </div>
            {children}
        </div>
    );
}

/**
 * Moves the service to another rate, so its next invoice uses that price.
 */
export function ChangeRateSection({
    service,
    rates,
}: {
    service: Service;
    rates: Rate[];
}) {
    const choices = rates.filter(
        (rate) => !rate.is_archived && priceIn(rate.prices, service.currency),
    );

    return (
        <SettingsSection
            title="Rate"
            description="What the service is priced from. A new rate applies from the next invoice."
        >
            <Form
                {...ChangeServiceRateController.form(service.id)}
                options={keepScroll}
            >
                {({ errors, processing }) => (
                    <SettingsRow
                        label="Rate"
                        htmlFor="rate_id"
                        description={
                            service.rate
                                ? `On ${service.rate.name}${service.rate.is_archived ? ', which is archived' : ''}.`
                                : 'Not on a rate yet, so it is not billed.'
                        }
                        error={errors.rate_id}
                    >
                        <div className="flex gap-2">
                            <RateCombobox
                                id="rate_id"
                                name="rate_id"
                                rates={choices}
                                defaultValue={
                                    service.rate?.is_archived
                                        ? ''
                                        : (service.rate_id ?? '')
                                }
                                detail={(rate) =>
                                    `${formatCents(
                                        priceIn(rate.prices, service.currency)
                                            ?.price_in_cents,
                                        service.currency,
                                    )} a month`
                                }
                                invalid={Boolean(errors.rate_id)}
                            />
                            <Button
                                variant="outline"
                                className="shrink-0"
                                disabled={processing}
                            >
                                Change rate
                            </Button>
                        </div>
                    </SettingsRow>
                )}
            </Form>
        </SettingsSection>
    );
}

/**
 * Turning the service on or off, auto-renew, archiving and deleting.
 */
export function ServiceLifecycleSections({
    service,
    can,
}: {
    service: Service;
    can: Abilities;
}) {
    const isArchived = service.lifecycle === 'archived';
    const isActive = service.status === 'active';
    const hasDangerZone =
        (isArchived ? can.restore : can.archive) || can.delete;

    return (
        <>
            <SettingsSection
                title="Status"
                description="Only active services are billed."
            >
                <ActionRow
                    title={isActive ? 'Active' : 'Inactive'}
                    description={
                        isActive
                            ? 'Billed on each invoice. Turning it off stops billing and auto-renew.'
                            : 'Kept on the customer, but not billed.'
                    }
                >
                    {can.update && !isArchived && (
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(
                                    (isActive
                                        ? DeactivateServiceController
                                        : ActivateServiceController
                                    ).url(service.id),
                                    {},
                                    keepScroll,
                                )
                            }
                        >
                            {isActive ? <PowerOff /> : <Power />}
                            {isActive ? 'Turn off' : 'Turn on'}
                        </Button>
                    )}
                </ActionRow>
                <ActionRow
                    title={
                        service.auto_renew
                            ? 'Renews by itself'
                            : "Doesn't renew by itself"
                    }
                    description={
                        isActive && !isArchived
                            ? 'For things renewed with a supplier, such as a domain name.'
                            : 'Turn the service on before it can renew by itself.'
                    }
                >
                    {can.update &&
                        (service.auto_renew || (isActive && !isArchived)) && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.put(
                                        SetServiceAutoRenewController.url(
                                            service.id,
                                        ),
                                        { auto_renew: !service.auto_renew },
                                        keepScroll,
                                    )
                                }
                            >
                                {service.auto_renew
                                    ? 'Stop renewing'
                                    : 'Renew by itself'}
                            </Button>
                        )}
                </ActionRow>
            </SettingsSection>

            {hasDangerZone && (
                <SettingsSection title="Danger zone" tone="destructive">
                    {isArchived
                        ? can.restore && (
                              <ActionRow
                                  title="Restore this service"
                                  description="Bring it back from the archive, as it was."
                              >
                                  <Button
                                      variant="outline"
                                      onClick={() =>
                                          router.post(
                                              RestoreServiceController.url(
                                                  service.id,
                                              ),
                                              {},
                                              keepScroll,
                                          )
                                      }
                                  >
                                      <ArchiveRestore />
                                      Restore
                                  </Button>
                              </ActionRow>
                          )
                        : can.archive && (
                              <ActionRow
                                  title="Archive this service"
                                  description="Put it away: it leaves the customer's list, is never billed and stops renewing. You can restore it."
                              >
                                  <Button
                                      variant="outline"
                                      onClick={() =>
                                          router.post(
                                              ArchiveServiceController.url(
                                                  service.id,
                                              ),
                                              {},
                                              keepScroll,
                                          )
                                      }
                                  >
                                      <Archive />
                                      Archive
                                  </Button>
                              </ActionRow>
                          )}
                    {can.delete && (
                        <ActionRow
                            title="Delete this service"
                            description="Invoices keep their lines, without the link to it. This can't be undone."
                        >
                            <ConfirmDelete
                                action={DeleteServiceController.form(
                                    service.id,
                                )}
                                title={`Delete ${service.name}?`}
                                description="This can't be undone. Archive it instead to keep its history."
                                trigger="Delete service"
                            />
                        </ActionRow>
                    )}
                </SettingsSection>
            )}
        </>
    );
}
