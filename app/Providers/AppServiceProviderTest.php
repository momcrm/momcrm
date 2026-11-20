<?php

declare(strict_types=1);

use App\Shared\MorphMapKeyEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

/**
 * Every Eloquent model class under app/, found from its *EloquentModel.php file.
 *
 * @return array<string, class-string<Model>>
 */
function eloquentModelClasses(): array
{
    return collect(glob(__DIR__.'/../*/*EloquentModel.php') ?: [])
        ->mapWithKeys(function (string $path): array {
            $class = 'App\\'.basename(dirname($path)).'\\'.basename($path, '.php');

            return [$class => $class];
        })
        ->all();
}

it('requires a morph map', function (): void {
    expect(Relation::requiresMorphMap())->toBeTrue();
});

it('gives every eloquent model a morph alias', function (string $model): void {
    expect(Relation::getMorphAlias($model))->not->toBe($model);
})->with(eloquentModelClasses());

it('keeps the stored morph aliases unchanged', function (): void {
    // These values are stored in polymorphic columns. Add new aliases, never rename existing ones.
    expect(MorphMapKeyEnum::values())->toBe(['access_rule', 'activity', 'article', 'article_category', 'article_link', 'article_version', 'asset', 'attachment', 'audit_log', 'bank_account', 'bank_rule', 'bank_transaction', 'charge', 'choice', 'comment', 'connector', 'contact', 'contract', 'customer', 'custom_field', 'custom_object', 'email_code', 'favorite', 'folder', 'group', 'invoice', 'invoice_item', 'location', 'mailbox', 'mailbox_message', 'milestone', 'passkey', 'payment', 'payment_allocation', 'product', 'project', 'rate', 'record', 'secret', 'service', 'social_account', 'team', 'team_invitation', 'team_user', 'ticket', 'user']);
});

it('maps every alias to its model and back', function (MorphMapKeyEnum $key): void {
    expect(Relation::getMorphedModel($key->value))->toBe($key->modelClass())
        ->and(MorphMapKeyEnum::fromModel(new ($key->modelClass())))->toBe($key);
})->with(MorphMapKeyEnum::cases());

it('throws for a model without a morph alias', function (): void {
    $unmapped = new class extends Model {};

    $unmapped->getMorphClass();
})->throws(ClassMorphViolationException::class);

it('sends the verification email in MomCRM copy', function (): void {
    $user = UserEloquentModel::factory()->unverified()->create(['name' => 'Jane Smith']);

    $mail = new VerifyEmail()->toMail($user);

    expect($mail->subject)->toBe('Confirm your email to finish setting up MomCRM')
        ->and($mail->greeting)->toBe('Hi Jane Smith,')
        ->and($mail->actionText)->toBe('Confirm my email')
        ->and($mail->actionUrl)->toContain('/email/verify/'.$user->id);
});

it('shows the app error page inside the app for an unknown address', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404)
            ->where('auth.user.id', $user->id),
        );
});

it('shows the error page to guests too', function (): void {
    get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 404)->where('auth.user', null));
});

it("shows the error page for another team's record", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('tickets.show', TicketEloquentModel::factory()->create()->id))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 404));
});

it('shows the error page when access is forbidden', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->get(route('access-rules.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 403));
});

it('keeps JSON errors for JSON requests', function (): void {
    getJson('/no-such-page')
        ->assertNotFound()
        ->assertHeaderMissing('X-Inertia')
        ->assertJsonStructure(['message']);
});
