<?php

declare(strict_types=1);

use App\Article\Actions\StoreArticleImage;
use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\Attachment\Attachment;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

function articleImage(ArticleEloquentModel $article, ?UploadedFile $file = null): Attachment
{
    return StoreArticleImage::for($file ?? UploadedFile::fake()->image('lights.png'), Article::fromEloquentModel($article), null);
}

it('shows a picture inline, telling the browser not to guess or run anything', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $image = articleImage($article);

    $response = actingAs($user)->get(route('articles.images.show', [$article->id, $image->getId()->toString()]))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/png')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Content-Security-Policy'))->toBe("default-src 'none'; sandbox");
});

it('hides pictures of articles the person may not read, here and as a file download', function (): void {
    Storage::fake('local');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create(['team_id' => $owner->current_team_id]);
    $image = articleImage($article);

    actingAs($member)->get(route('articles.images.show', [$article->id, $image->getId()->toString()]))->assertNotFound();
    actingAs($member)->get(route('attachments.show', $image->getId()->toString()))->assertNotFound();
    actingAs($owner)->get(route('articles.images.show', [$article->id, $image->getId()->toString()]))->assertOk();
});

it("won't show a picture under another article's address", function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $other = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $image = articleImage($other);

    actingAs($user)->get(route('articles.images.show', [$article->id, $image->getId()->toString()]))->assertNotFound();
});
