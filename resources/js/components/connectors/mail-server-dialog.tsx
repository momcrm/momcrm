import { Form } from '@inertiajs/react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { store } from '@/routes/connectors';
import type { Connector } from '@/types';

const encryptions = [
    { value: 'ssl', label: 'SSL/TLS' },
    { value: 'starttls', label: 'STARTTLS' },
    { value: 'none', label: 'None' },
];

/**
 * Connects a mailbox on any mail server by its IMAP and SMTP servers, or
 * changes the servers and passwords of one that is already connected.
 * Passwords are never sent back to the page, so they are typed in each time.
 */
export default function MailServerDialog({
    open,
    onOpenChange,
    connector,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The connector being changed; none when a new one is being added. */
    connector: Connector | null;
}) {
    const settings = connector?.settings ?? null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogTitle>
                    {connector
                        ? `Change ${connector.account}`
                        : 'Connect a mailbox by its mail servers'}
                </DialogTitle>
                <DialogDescription>
                    MomCRM reads the mailbox over IMAP and sends replies over
                    SMTP. Your mail provider lists these settings, often under
                    “mail client setup”.
                </DialogDescription>
                <Form
                    {...store.form()}
                    // A new form for each connector, so its values are not left over from the last one.
                    key={connector?.id ?? 'new'}
                    className="space-y-4"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <FormField
                                    id="mail-from-address"
                                    label="Email address"
                                    hint={
                                        connector
                                            ? 'To use another address, add it as a new connector.'
                                            : undefined
                                    }
                                    error={errors.from_address}
                                >
                                    <Input
                                        id="mail-from-address"
                                        name="from_address"
                                        type="email"
                                        required
                                        readOnly={connector !== null}
                                        defaultValue={connector?.account ?? ''}
                                        placeholder="support@yourcompany.com"
                                        autoComplete="off"
                                    />
                                </FormField>
                                <FormField
                                    id="mail-from-name"
                                    label="Name customers see"
                                    error={errors.from_name}
                                >
                                    <Input
                                        id="mail-from-name"
                                        name="from_name"
                                        defaultValue={settings?.from_name ?? ''}
                                        placeholder="Optional"
                                        autoComplete="off"
                                    />
                                </FormField>
                            </div>

                            {(
                                [
                                    {
                                        prefix: 'imap',
                                        title: 'Incoming mail (IMAP)',
                                        port: 993,
                                        encryption: 'ssl',
                                        host: 'imap.yourcompany.com',
                                        sameAsIncoming: false,
                                    },
                                    {
                                        prefix: 'smtp',
                                        title: 'Outgoing mail (SMTP)',
                                        port: 587,
                                        encryption: 'starttls',
                                        host: 'smtp.yourcompany.com',
                                        sameAsIncoming: true,
                                    },
                                ] as const
                            ).map((server) => (
                                <fieldset
                                    key={server.prefix}
                                    className="space-y-3 rounded-lg border p-3"
                                >
                                    <legend className="px-1 text-sm font-medium">
                                        {server.title}
                                    </legend>
                                    <div className="grid gap-3 sm:grid-cols-[1fr_6rem_8rem]">
                                        <FormField
                                            id={`mail-${server.prefix}-host`}
                                            label="Server"
                                            error={
                                                errors[`${server.prefix}_host`]
                                            }
                                        >
                                            <Input
                                                id={`mail-${server.prefix}-host`}
                                                name={`${server.prefix}_host`}
                                                required
                                                defaultValue={
                                                    settings?.[
                                                        `${server.prefix}_host`
                                                    ] ?? ''
                                                }
                                                placeholder={server.host}
                                                autoComplete="off"
                                            />
                                        </FormField>
                                        <FormField
                                            id={`mail-${server.prefix}-port`}
                                            label="Port"
                                            error={
                                                errors[`${server.prefix}_port`]
                                            }
                                        >
                                            <Input
                                                id={`mail-${server.prefix}-port`}
                                                name={`${server.prefix}_port`}
                                                type="number"
                                                min={1}
                                                max={65535}
                                                required
                                                defaultValue={
                                                    settings?.[
                                                        `${server.prefix}_port`
                                                    ] ?? server.port
                                                }
                                            />
                                        </FormField>
                                        <FormField
                                            id={`mail-${server.prefix}-encryption`}
                                            label="Encryption"
                                            error={
                                                errors[
                                                    `${server.prefix}_encryption`
                                                ]
                                            }
                                        >
                                            <NativeSelect
                                                id={`mail-${server.prefix}-encryption`}
                                                name={`${server.prefix}_encryption`}
                                                defaultValue={
                                                    settings?.[
                                                        `${server.prefix}_encryption`
                                                    ] ?? server.encryption
                                                }
                                            >
                                                {encryptions.map((option) => (
                                                    <option
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        </FormField>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <FormField
                                            id={`mail-${server.prefix}-username`}
                                            label="Username"
                                            hint={
                                                server.sameAsIncoming
                                                    ? 'Leave empty to use the incoming one.'
                                                    : undefined
                                            }
                                            error={
                                                errors[
                                                    `${server.prefix}_username`
                                                ]
                                            }
                                        >
                                            <Input
                                                id={`mail-${server.prefix}-username`}
                                                name={`${server.prefix}_username`}
                                                required={
                                                    !server.sameAsIncoming
                                                }
                                                defaultValue={
                                                    settings?.[
                                                        `${server.prefix}_username`
                                                    ] ?? ''
                                                }
                                                autoComplete="off"
                                            />
                                        </FormField>
                                        <FormField
                                            id={`mail-${server.prefix}-password`}
                                            label="Password"
                                            hint={
                                                server.sameAsIncoming
                                                    ? 'Leave empty to use the incoming one.'
                                                    : undefined
                                            }
                                            error={
                                                errors[
                                                    `${server.prefix}_password`
                                                ]
                                            }
                                        >
                                            <Input
                                                id={`mail-${server.prefix}-password`}
                                                name={`${server.prefix}_password`}
                                                type="password"
                                                required={
                                                    !server.sameAsIncoming
                                                }
                                                autoComplete="new-password"
                                            />
                                        </FormField>
                                    </div>
                                </fieldset>
                            ))}

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Signing in to the servers…'
                                        : connector
                                          ? 'Save'
                                          : 'Connect'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
