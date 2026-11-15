<?php

declare(strict_types=1);

namespace App\AccessRule\FormRequests;

use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleValidationRules;
use App\AccessRule\Actions\CreateAccessRule;
use App\AccessRule\Properties\Conditions;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Adds an access rule. Only owners and admins manage the team's rules.
 */
final class StoreAccessRuleFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        $team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Gate::authorize('manageAccessRules', $team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return AccessRuleValidationRules::for($this);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return AccessRuleValidationRules::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => AccessRuleValidationRules::after(
            $validator,
            (string) $this->user()?->current_team_id,
            (string) $this->user()?->id,
        )];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        $rule = CreateAccessRule::fromAccessRule(
            AccessRule::new()
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, [Conditions::getNameForValidation()])))
                ->setConditions(AccessRuleValidationRules::normalizeConditions($validated[Conditions::getNameForValidation()]))
                ->setTeamId((string) $this->user()?->current_team_id)
                ->setCreatedById((string) $this->user()?->id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $rule->getName()->toString()])]);

        return to_route('access-rules.index');
    }
}
