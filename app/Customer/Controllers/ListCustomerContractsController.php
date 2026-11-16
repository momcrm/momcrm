<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Attachment;
use App\Contract\Actions\FindContract;
use App\Contract\Contract;
use App\Contract\ContractStatusEnum;
use App\Contract\ContractTypeEnum;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's contracts tab, with each contract's documents.
 */
final class ListCustomerContractsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $status = ContractStatusEnum::tryFrom($request->string('status')->toString());
        $type = ContractTypeEnum::tryFrom($request->string('type')->toString());
        $sort = in_array($request->string('sort')->toString(), FindContract::SORTS, true) ? $request->string('sort')->toString() : 'title';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $contracts = FindContract::forCustomer($workspace->customerId(), $workspace->teamId, $status, $type, $sort, $direction);
        $documents = FindAttachment::forAttachables(
            MorphMapKeyEnum::Contract,
            $contracts->map(fn (Contract $contract): string => $contract->getId()->toString())->values()->all(),
        );

        return Inertia::render('customers/workspace/contracts', [
            ...$workspace->props($request),
            'contracts' => $contracts
                ->map(fn (Contract $contract): array => [
                    ...$contract->toSummary(),
                    'documents' => ($documents[$contract->getId()->toString()] ?? null)
                        ?->map(fn (Attachment $attachment): array => FindAttachment::listItem($attachment))
                        ->values()
                        ->all() ?? [],
                ])
                ->values()
                ->all(),
            'filters' => ['status' => $status?->value, 'type' => $type?->value, 'sort' => $sort, 'direction' => $direction],
            'types' => ContractTypeEnum::options(),
            'statuses' => ContractStatusEnum::options(),
            'contractCan' => AccessAbilities::for($request->user(), AccessResourceEnum::Contract),
            'documentCan' => AccessAbilities::for($request->user(), AccessResourceEnum::Attachment),
        ]);
    }
}
