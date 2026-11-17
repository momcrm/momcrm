<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomObject\StandardObjectEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Custom fields used to have a settings page of their own, with a tab per
 * kind of record. They are now managed on each object's page in Settings →
 * Objects, and this address sends old links there.
 */
final class ShowCustomFieldsController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $object = $request->string('object')->toString();
        $target = $request->string('target')->toString();

        return to_route('custom-objects.fields', match (true) {
            Str::isUuid($object) => $object,
            StandardObjectEnum::tryFrom($target) !== null => $target,
            default => StandardObjectEnum::Ticket->value,
        });
    }
}
