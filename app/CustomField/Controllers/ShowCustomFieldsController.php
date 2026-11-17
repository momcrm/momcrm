<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The fields the team has added to each kind of record, for owners and admins
 * to edit. `?target=` picks which kind of record is open, or `?object=` one
 * of the team's custom objects.
 */
final class ShowCustomFieldsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        CustomFieldAccess::authorize($request);
        $teamId = CustomFieldAccess::team($request)->getId()->toString();

        $objects = FindCustomObject::listForTeam($teamId);
        $object = $objects->first(fn (CustomObject $object): bool => $object->getId()->equalsTo($request->string('object')->toString()));
        $current = $object->isNotNull()
            ? CustomFieldTargetEnum::Record
            : (collect(CustomFieldTargetEnum::builtIn())->first(fn (CustomFieldTargetEnum $target): bool => $target->value === $request->string('target')->toString()) ?? CustomFieldTargetEnum::Ticket);

        return Inertia::render('settings/custom-fields', [
            'current' => $current->value,
            'currentObject' => $object->getId()->toNullableString(),
            'targets' => array_map(fn (CustomFieldTargetEnum $target): array => ['value' => $target->value, 'label' => $target->label()], CustomFieldTargetEnum::builtIn()),
            'objects' => array_values($objects->map(fn (CustomObject $object): array => ['value' => $object->getId()->toString(), 'label' => $object->getNamePlural()->toString()])->all()),
            'types' => array_map(fn (CustomFieldTypeEnum $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'has_options' => $type->hasOptions(),
            ], CustomFieldTypeEnum::cases()),
            'relations' => CustomFieldRelationEnum::options(),
            'fields' => FindCustomField::listForTeam($teamId, $current, withArchived: true, objectId: $object->getId()->toNullableString())->toSummaries(),
        ]);
    }
}
