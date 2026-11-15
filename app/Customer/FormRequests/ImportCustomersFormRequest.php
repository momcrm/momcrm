<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\ImportCustomersFromCsv;
use App\Customer\Customer;
use App\Onboarding\Actions\CompleteOnboarding;
use App\User\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

final class ImportCustomersFormRequest extends FormRequest
{
    public const int MAX_KILOBYTES = 1024;

    public function authorize(): Response
    {
        return Gate::authorize('import', Customer::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose a CSV file to import.'),
            'file.mimes' => __('Export your spreadsheet as CSV, then choose that file.'),
            'file.max' => __('That file is over 1 MB. Split it and import each part.'),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function process(string $redirectRoute, bool $completesOnboarding = false): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        $result = ImportCustomersFromCsv::fromFile(
            path: (string) $file->getRealPath(),
            teamId: (string) $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($completesOnboarding) {
            CompleteOnboarding::fromUser(User::fromEloquentModel($this->user()));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(
            '{1} Imported 1 customer.|[2,*] Imported :count customers.',
            $result['customers'],
        ).($result['contacts'] > 0 ? ' '.trans_choice('{1} 1 contact came with them.|[2,*] :count contacts came with them.', $result['contacts']) : '')]);

        return to_route($redirectRoute);
    }
}
