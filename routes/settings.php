<?php

use App\AccessRule\Controllers\DeleteAccessRuleController;
use App\AccessRule\Controllers\ListAccessRulesController;
use App\AccessRule\Controllers\StoreAccessRuleController;
use App\AccessRule\Controllers\UpdateAccessRuleController;
use App\Activity\Controllers\ListAccountActivityController;
use App\Activity\Controllers\ListTeamActivityController;
use App\Choice\Controllers\ArchiveChoiceController;
use App\Choice\Controllers\DeleteChoiceController;
use App\Choice\Controllers\ReorderChoicesController;
use App\Choice\Controllers\RestoreChoiceController;
use App\Choice\Controllers\ShowWorkflowController;
use App\Choice\Controllers\StoreChoiceController;
use App\Choice\Controllers\UpdateChoiceController;
use App\CustomField\Controllers\ArchiveCustomFieldController;
use App\CustomField\Controllers\DeleteCustomFieldController;
use App\CustomField\Controllers\ReorderCustomFieldsController;
use App\CustomField\Controllers\RestoreCustomFieldController;
use App\CustomField\Controllers\ShowCustomFieldsController;
use App\CustomField\Controllers\StoreCustomFieldController;
use App\CustomField\Controllers\UpdateCustomFieldController;
use App\CustomObject\Controllers\ArchiveCustomObjectController;
use App\CustomObject\Controllers\DeleteCustomObjectController;
use App\CustomObject\Controllers\ReorderCustomObjectsController;
use App\CustomObject\Controllers\RestoreCustomObjectController;
use App\CustomObject\Controllers\ShowCustomObjectsController;
use App\CustomObject\Controllers\ShowObjectFieldsController;
use App\CustomObject\Controllers\StoreCustomObjectController;
use App\CustomObject\Controllers\UpdateCustomObjectController;
use App\Mailbox\Controllers\ConnectMailboxController;
use App\Mailbox\Controllers\DeleteMailboxController;
use App\Mailbox\Controllers\HandleMailboxCallbackController;
use App\Mailbox\Controllers\ShowEmailSettingsController;
use App\Mailbox\Controllers\UpdateMailboxController;
use App\Team\Controllers\EditTeamController;
use App\Team\Controllers\UpdateTeamController;
use App\TeamInvitation\Controllers\DeleteTeamInvitationController;
use App\TeamInvitation\Controllers\ResendTeamInvitationController;
use App\TeamInvitation\Controllers\StoreTeamInvitationController;
use App\User\Controllers\DeleteProfileController;
use App\User\Controllers\EditProfileController;
use App\User\Controllers\EditSecurityController;
use App\User\Controllers\UpdatePasswordController;
use App\User\Controllers\UpdateProfileController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', EditProfileController::class)->name('profile.edit');
    Route::patch('settings/profile', UpdateProfileController::class)->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', DeleteProfileController::class)->name('profile.destroy');

    Route::get('settings/security', EditSecurityController::class)
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', UpdatePasswordController::class)
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/team', EditTeamController::class)->name('team.edit');
    Route::patch('settings/team', UpdateTeamController::class)->name('team.update');
    Route::post('settings/team/invitations', StoreTeamInvitationController::class)->name('team.invitations.store');
    Route::delete('settings/team/invitations/{invitation}', DeleteTeamInvitationController::class)->name('team.invitations.destroy');
    Route::post('settings/team/invitations/{invitation}/resend', ResendTeamInvitationController::class)->name('team.invitations.resend');
    Route::get('settings/team/activity', ListTeamActivityController::class)->name('team-activity.index');

    Route::get('settings/access-rules', ListAccessRulesController::class)->name('access-rules.index');
    Route::post('settings/access-rules', StoreAccessRuleController::class)->name('access-rules.store');
    Route::put('settings/access-rules/{accessRule}', UpdateAccessRuleController::class)->name('access-rules.update');
    Route::delete('settings/access-rules/{accessRule}', DeleteAccessRuleController::class)->name('access-rules.destroy');

    Route::get('settings/workflow', ShowWorkflowController::class)->name('workflow.edit');
    Route::post('settings/choices', StoreChoiceController::class)->name('choices.store');
    Route::put('settings/choices/order', ReorderChoicesController::class)->name('choices.reorder');
    Route::patch('settings/choices/{choice}', UpdateChoiceController::class)->whereUuid('choice')->name('choices.update');
    Route::post('settings/choices/{choice}/archive', ArchiveChoiceController::class)->whereUuid('choice')->name('choices.archive');
    Route::post('settings/choices/{choice}/restore', RestoreChoiceController::class)->whereUuid('choice')->name('choices.restore');
    Route::delete('settings/choices/{choice}', DeleteChoiceController::class)->whereUuid('choice')->name('choices.destroy');

    Route::get('settings/custom-fields', ShowCustomFieldsController::class)->name('custom-fields.edit');
    Route::post('settings/custom-fields', StoreCustomFieldController::class)->name('custom-fields.store');
    Route::put('settings/custom-fields/order', ReorderCustomFieldsController::class)->name('custom-fields.reorder');
    Route::patch('settings/custom-fields/{customField}', UpdateCustomFieldController::class)->whereUuid('customField')->name('custom-fields.update');
    Route::post('settings/custom-fields/{customField}/archive', ArchiveCustomFieldController::class)->whereUuid('customField')->name('custom-fields.archive');
    Route::post('settings/custom-fields/{customField}/restore', RestoreCustomFieldController::class)->whereUuid('customField')->name('custom-fields.restore');
    Route::delete('settings/custom-fields/{customField}', DeleteCustomFieldController::class)->whereUuid('customField')->name('custom-fields.destroy');

    Route::get('settings/objects', ShowCustomObjectsController::class)->name('custom-objects.edit');
    Route::get('settings/objects/{object}', ShowObjectFieldsController::class)->where('object', '[a-z]+|[0-9a-f-]{36}')->name('custom-objects.fields');
    Route::post('settings/objects', StoreCustomObjectController::class)->name('custom-objects.store');
    Route::put('settings/objects/order', ReorderCustomObjectsController::class)->name('custom-objects.reorder');
    Route::patch('settings/objects/{customObject}', UpdateCustomObjectController::class)->whereUuid('customObject')->name('custom-objects.update');
    Route::post('settings/objects/{customObject}/archive', ArchiveCustomObjectController::class)->whereUuid('customObject')->name('custom-objects.archive');
    Route::post('settings/objects/{customObject}/restore', RestoreCustomObjectController::class)->whereUuid('customObject')->name('custom-objects.restore');
    Route::delete('settings/objects/{customObject}', DeleteCustomObjectController::class)->whereUuid('customObject')->name('custom-objects.destroy');

    Route::get('settings/email', ShowEmailSettingsController::class)->name('mailboxes.index');
    Route::get('settings/email/connect', ConnectMailboxController::class)->name('mailboxes.connect');
    Route::get('settings/email/callback', HandleMailboxCallbackController::class)->name('mailboxes.callback');
    Route::put('settings/email/mailboxes/{mailbox}', UpdateMailboxController::class)->whereUuid('mailbox')->name('mailboxes.update');
    Route::delete('settings/email/mailboxes/{mailbox}', DeleteMailboxController::class)->whereUuid('mailbox')->name('mailboxes.destroy');

    Route::get('settings/activity', ListAccountActivityController::class)->name('activity.index');

    Route::get('settings/appearance', fn (): Response => Inertia::render('settings/appearance'))->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
