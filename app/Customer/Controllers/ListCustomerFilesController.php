<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Folder\Actions\FindFolder;
use App\Folder\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's files tab: the folder tree, and the files and folders in
 * the open folder (or the top level).
 */
final class ListCustomerFilesController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $folders = FindFolder::forCustomer($workspace->customerId(), $workspace->teamId);
        $requested = $request->string('folder')->toString();
        $openFolder = $folders->first(fn (Folder $folder): bool => $folder->getId()->toString() === $requested);
        $folderId = $openFolder->getId()->toNullableString();

        return Inertia::render('customers/workspace/files', [
            ...$workspace->props($request),
            'folders' => $folders->map(fn (Folder $folder): array => $folder->toSummary())->values()->all(),
            'folderId' => $folderId,
            'files' => FindAttachment::inCustomerFolder($workspace->customerId(), $folderId)
                ->map(fn (Attachment $attachment): array => FindAttachment::listItem($attachment))
                ->values()
                ->all(),
            'accept' => AttachmentValidationRules::ACCEPT,
            'fileCan' => AccessAbilities::for($request->user(), AccessResourceEnum::Attachment),
        ]);
    }
}
