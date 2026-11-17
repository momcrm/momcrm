<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\CustomObject\StandardObjectEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One object's fields, for owners and admins: a built-in object's own fields
 * (read-only) and the custom fields the team has added, or a custom object's
 * fields. `{object}` is a built-in kind ("ticket") or a custom object's id.
 */
final class ShowObjectFieldsController
{
    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindCustomObject
     */
    public function __invoke(Request $request, string $object): Response
    {
        CustomFieldAccess::authorize($request);
        $teamId = CustomFieldAccess::team($request)->getId()->toString();

        $objects = FindCustomObject::listForTeam($teamId);
        $standard = StandardObjectEnum::tryFrom($object);
        $custom = $objects->first(fn (CustomObject $custom): bool => $custom->getId()->equalsTo($object));

        if ($standard === null && $custom->isNull()) {
            throw CouldNotFindCustomObject::notFound();
        }

        $target = $standard === null ? CustomFieldTargetEnum::Record : CustomFieldTargetEnum::from($standard->value);

        return Inertia::render('settings/object-fields', [
            'object' => $standard === null
                ? ['key' => $custom->getId()->toString(), 'label' => $custom->getNamePlural()->toString(), 'icon' => $custom->getIcon()->toNullableString(), 'hue' => $custom->getHue()->toNullableInteger(), 'is_standard' => false]
                : ['key' => $standard->value, 'label' => $standard->label(), 'icon' => $standard->icon(), 'hue' => null, 'is_standard' => true],
            'current' => $target->value,
            'currentObject' => $custom->getId()->toNullableString(),
            'builtInFields' => $standard?->fields() ?? [['name' => 'Name', 'type' => 'Text']],
            'objects' => array_values($objects->map(fn (CustomObject $custom): array => ['value' => $custom->getId()->toString(), 'label' => $custom->getNamePlural()->toString()])->all()),
            'types' => array_map(fn (CustomFieldTypeEnum $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'has_options' => $type->hasOptions(),
            ], CustomFieldTypeEnum::cases()),
            'relations' => CustomFieldRelationEnum::options(),
            'fields' => FindCustomField::listForTeam($teamId, $target, withArchived: true, objectId: $custom->getId()->toNullableString())->toSummaries(),
        ]);
    }
}
