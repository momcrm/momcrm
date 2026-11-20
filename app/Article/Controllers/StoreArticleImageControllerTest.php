<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('keeps a picture on the article and answers with its address', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    $response = actingAs($user)
        ->post(route('articles.images.store', $article->id), ['image' => UploadedFile::fake()->image('lights.png')])
        ->assertCreated();

    $image = AttachmentEloquentModel::query()->sole();
    $response->assertExactJson(['src' => "/articles/{$article->id}/images/{$image->id}"]);

    expect($image)
        ->attachable_type->toBe('article')
        ->attachable_id->toBe($article->id)
        ->uploaded_by_id->toBe($user->id)
        ->mime_type->toBe('image/png');
    Storage::disk('local')->assertExists($image->path);
});

it('only takes pictures', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->postJson(route('articles.images.store', $article->id), ['image' => UploadedFile::fake()->create('page.svg', 2, 'image/svg+xml')])
        ->assertJsonValidationErrors(['image' => 'Pictures can be PNG, JPEG, GIF or WebP.']);

    expect(AttachmentEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's article", function (): void {
    Storage::fake('local');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('articles.images.store', ArticleEloquentModel::factory()->create()->id), ['image' => UploadedFile::fake()->image('lights.png')])
        ->assertNotFound();
});
