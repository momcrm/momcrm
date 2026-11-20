import { Form, Head, router } from '@inertiajs/react';
import {
    EyeOff,
    Landmark,
    Pencil,
    Plus,
    Trash2,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import BankAccountFormDialog from '@/components/bank-feeds/bank-account-form-dialog';
import BankRuleFormDialog from '@/components/bank-feeds/bank-rule-form-dialog';
import Heading from '@/components/heading';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { timeAgo } from '@/lib/format';
import { destroy } from '@/routes/bank-accounts';
import { index as bankFeedsIndex } from '@/routes/bank-feeds';
import { destroy as destroyRule } from '@/routes/bank-rules';
import type { BankAccount, BankRule, CustomerOption, Option } from '@/types';

type Editing = { account: BankAccount } | 'new' | null;

export default function BankFeedSettings({
    accounts,
    currencies,
    kinds,
    rules,
    ruleKinds,
    customers,
}: {
    accounts: BankAccount[];
    currencies: Option[];
    kinds: Option[];
    rules: BankRule[];
    ruleKinds: Option[];
    customers: CustomerOption[];
}) {
    const [editing, setEditing] = useState<Editing>(null);
    const [editingRule, setEditingRule] = useState<
        { rule: BankRule } | 'new' | null
    >(null);
    const [deleting, setDeleting] = useState<BankAccount | null>(null);

    return (
        <>
            <Head title="Bank feeds" />

            <h1 className="sr-only">Bank feeds</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Bank accounts"
                        description="The accounts your customers pay into. Each deposit in a statement you import becomes a line to match to a customer."
                    />
                    <Button
                        size="sm"
                        className="shrink-0"
                        onClick={() => setEditing('new')}
                    >
                        <Plus />
                        Add account
                    </Button>
                </div>

                {accounts.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No bank accounts yet. Add the one your customers pay
                        into, then import a statement exported from your online
                        banking.
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {accounts.map((account) => (
                            <li
                                key={account.id}
                                className="flex items-center gap-3 py-2 pr-3 pl-3"
                            >
                                <Landmark className="size-4 shrink-0 text-subtle-foreground" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {account.name}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {[
                                            account.currency,
                                            account.kind_label,
                                            account.last_imported_at
                                                ? `last imported ${timeAgo(account.last_imported_at)}`
                                                : 'nothing imported yet',
                                        ].join(' · ')}
                                    </p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={`Edit ${account.name}`}
                                    onClick={() => setEditing({ account })}
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={`Delete ${account.name}`}
                                    onClick={() => setDeleting(account)}
                                >
                                    <Trash2 />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}

                <section className="space-y-3 pt-4">
                    <div className="flex items-start justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Rules"
                            description="Say who deposits are from, or hide ones that aren't customers' payments, by text in their description. Rules come first when deposits are matched."
                        />
                        <Button
                            size="sm"
                            variant="outline"
                            className="shrink-0"
                            onClick={() => setEditingRule('new')}
                        >
                            <Plus />
                            Add rule
                        </Button>
                    </div>
                    {rules.length === 0 ? (
                        <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            No rules yet. Add one here, or tick “Remember” when
                            you choose a deposit’s customer.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {rules.map((rule) => (
                                <li
                                    key={rule.id}
                                    className="flex items-center gap-3 py-2 pr-3 pl-3"
                                >
                                    {rule.kind === 'hide' ? (
                                        <EyeOff className="size-4 shrink-0 text-subtle-foreground" />
                                    ) : (
                                        <UserRound className="size-4 shrink-0 text-subtle-foreground" />
                                    )}
                                    <p className="min-w-0 flex-1 truncate text-sm">
                                        Contains{' '}
                                        <span className="font-mono text-xs font-medium">
                                            “{rule.contains}”
                                        </span>{' '}
                                        →{' '}
                                        {rule.kind === 'hide'
                                            ? 'hide it'
                                            : (rule.customer_name ??
                                              'a deleted customer')}
                                    </p>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label={`Edit the rule for ${rule.contains}`}
                                        onClick={() => setEditingRule({ rule })}
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label={`Delete the rule for ${rule.contains}`}
                                        onClick={() =>
                                            router.delete(
                                                destroyRule.url(rule.id),
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <p className="text-sm text-muted-foreground">
                    Import statements on{' '}
                    <TextLink href={bankFeedsIndex()}>Bank feeds</TextLink>.
                </p>
            </div>

            <BankAccountFormDialog
                key={
                    editing === null
                        ? 'closed'
                        : editing === 'new'
                          ? 'new'
                          : editing.account.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                currencies={currencies}
                kinds={kinds}
                account={
                    editing && editing !== 'new' ? editing.account : undefined
                }
            />

            <BankRuleFormDialog
                key={
                    editingRule === null
                        ? 'closed'
                        : editingRule === 'new'
                          ? 'new'
                          : editingRule.rule.id
                }
                open={editingRule !== null}
                onOpenChange={(open) => !open && setEditingRule(null)}
                kinds={ruleKinds}
                customers={customers}
                rule={
                    editingRule && editingRule !== 'new'
                        ? editingRule.rule
                        : undefined
                }
            />

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogTitle>Delete {deleting?.name}?</DialogTitle>
                    <DialogDescription>
                        The bank feed lines imported into it are deleted too.
                        Payments already recorded from them stay.
                    </DialogDescription>
                    {deleting && (
                        <Form
                            {...destroy.form(deleting.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setDeleting(null)}
                        >
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            variant="secondary"
                                            type="button"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Delete account
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
