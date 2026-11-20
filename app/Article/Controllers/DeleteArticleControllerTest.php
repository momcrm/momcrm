<?php

declare(strict_types=1);

use App\Article\Actions\StoreArticleImage;
use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('deletes an article and returns to the list', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->delete(route('articles.destroy', $article->id))->assertRedirectToRoute('articles.index');

    expect(ArticleEloquentModel::query()->exists())->toBeFalse();
});

it('leaves deleting to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('articles.destroy', $article->id))->assertForbidden();

    expect(ArticleEloquentModel::query()->exists())->toBeTrue();
});

it("deletes the article's pictures with it", function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $image = StoreArticleImage::for(UploadedFile::fake()->image('lights.png'), Article::fromEloquentModel($article), $user->id);

    actingAs($user)->delete(route('articles.destroy', $article->id));

    expect(AttachmentEloquentModel::query()->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($image->getPath()->toString());
});
