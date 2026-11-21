<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Storage::fake('local'));

it("shows a customer article's picture, but not an internal article's", function (): void {
    $contact = portalContact();
    $article = ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create(['team_id' => $contact->team_id]);
    $internal = ArticleEloquentModel::factory()->published()->create(['team_id' => $contact->team_id]);
    Storage::disk('local')->put('attachments/pic.png', 'png');
    $picture = AttachmentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'attachable_type' => 'article', 'attachable_id' => $article->id, 'path' => 'attachments/pic.png', 'mime_type' => 'image/png']);
    $hidden = AttachmentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'attachable_type' => 'article', 'attachable_id' => $internal->id, 'path' => 'attachments/pic.png', 'mime_type' => 'image/png']);

    actingAs($contact, 'portal')
        ->get(route('articles.images.show', [$article->id, $picture->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    actingAs($contact, 'portal')->get(route('articles.images.show', [$internal->id, $hidden->id]))->assertNotFound();
    actingAs($contact, 'portal')->get(route('articles.images.show', [$article->id, $hidden->id]))->assertNotFound();
});
