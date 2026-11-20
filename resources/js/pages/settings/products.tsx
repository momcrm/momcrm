import { Head, router } from '@inertiajs/react';
import { Archive, ArchiveRestore, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import ProductFormDialog from '@/components/products/product-form-dialog';
import { Button } from '@/components/ui/button';
import { formatPrices } from '@/lib/money';
import { archive, restore } from '@/routes/products';
import type { Option, Product } from '@/types';

function ProductRow({
    product,
    onEdit,
}: {
    product: Product;
    onEdit: (product: Product) => void;
}) {
    return (
        <li className="flex items-center gap-2 py-2 pr-3 pl-3">
            <div className="min-w-0 flex-1">
                <p
                    className={
                        product.is_archived
                            ? 'truncate text-sm text-muted-foreground'
                            : 'truncate text-sm font-medium'
                    }
                >
                    {product.name}
                </p>
                <p className="truncate text-xs text-muted-foreground">
                    {[formatPrices(product.prices), product.description]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            </div>
            {product.is_archived ? (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-7"
                    onClick={() =>
                        router.post(
                            restore.url(product.id),
                            {},
                            { preserveScroll: true },
                        )
                    }
                >
                    <ArchiveRestore />
                    Restore
                </Button>
            ) : (
                <>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Edit ${product.name}`}
                        onClick={() => onEdit(product)}
                    >
                        <Pencil />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Archive ${product.name}`}
                        title="Archive: no longer offered for new charges"
                        onClick={() =>
                            router.post(
                                archive.url(product.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <Archive />
                    </Button>
                </>
            )}
        </li>
    );
}

export default function Products({
    products,
    currencies,
}: {
    products: Product[];
    currencies: Option[];
}) {
    const [editing, setEditing] = useState<{ product: Product } | 'new' | null>(
        null,
    );
    const live = products.filter((product) => !product.is_archived);
    const archived = products.filter((product) => product.is_archived);
    const edit = (product: Product) => setEditing({ product });

    return (
        <>
            <Head title="Products" />

            <h1 className="sr-only">Products</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Products"
                        description="Things you sell once, such as hardware or a call-out. Charge one to a customer and it goes on their next invoice. Monthly things are rates."
                    />
                    <Button
                        size="sm"
                        className="shrink-0"
                        onClick={() => setEditing('new')}
                    >
                        <Plus />
                        Add product
                    </Button>
                </div>

                {live.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No products yet. Add the things you often charge for
                        once, so a charge can start from their price.
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {live.map((product) => (
                            <ProductRow
                                key={product.id}
                                product={product}
                                onEdit={edit}
                            />
                        ))}
                    </ul>
                )}

                {archived.length > 0 && (
                    <section className="space-y-2">
                        <div>
                            <h2 className="text-sm font-medium">Archived</h2>
                            <p className="text-xs text-muted-foreground">
                                Not offered for new charges.
                            </p>
                        </div>
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {archived.map((product) => (
                                <ProductRow
                                    key={product.id}
                                    product={product}
                                    onEdit={edit}
                                />
                            ))}
                        </ul>
                    </section>
                )}
            </div>

            <ProductFormDialog
                key={
                    editing === null
                        ? 'closed'
                        : editing === 'new'
                          ? 'new'
                          : editing.product.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                currencies={currencies}
                product={
                    editing && editing !== 'new' ? editing.product : undefined
                }
            />
        </>
    );
}
