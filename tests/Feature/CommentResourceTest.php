<?php

use App\Filament\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Form Tests
describe('CommentResource Form', function () {
  beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create();
  });

  // Field Existence and Configuration
  it('has all required form fields with correct configurations', function () {
    livewire(CommentResource\Pages\CreateComment::class)
      ->assertFormExists()
      ->assertFormFieldExists('post_id', function (Select $field) {
        return $field->isRequired() &&
          $field->isSearchable() &&
          $field->getRelationshipName() === 'post' &&
          $field->getLabel() === 'Post';
      })
      ->assertFormFieldExists('user_id', function (Select $field) {
        return $field->isRequired() &&
          $field->isSearchable() &&
          $field->getRelationshipName() === 'user' &&
          $field->getLabel() === 'User';
      })
      ->assertFormFieldExists('body', function (Textarea $field) {
        return $field->isRequired() &&
          $field->getLabel() === 'Comment Text';
      });
  });

  // Validation Tests
  it('validates required fields', function () {
    livewire(CommentResource\Pages\CreateComment::class)
      ->fillForm([
        'post_id' => null,
        'user_id' => null,
        'body' => null,
      ])
      ->call('create')
      ->assertHasFormErrors([
        'post_id' => 'required',
        'user_id' => 'required',
        'body' => 'required',
      ]);
  });

  // Form State Tests
  it('can create a comment with valid data', function () {
    livewire(CommentResource\Pages\CreateComment::class)
      ->fillForm([
        'post_id' => $this->post->id,
        'user_id' => $this->user->id,
        'body' => 'Test comment content',
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    $comment = Comment::first();
    expect($comment)
      ->post_id->toBe($this->post->id)
      ->user_id->toBe($this->user->id)
      ->body->toBe('Test comment content');
  });

  it('can edit a comment with valid data', function () {
    $comment = Comment::factory()->create([
      'post_id' => $this->post->id,
      'user_id' => $this->user->id,
    ]);
    $newPost = Post::factory()->create();
    $newUser = User::factory()->create();

    livewire(CommentResource\Pages\EditComment::class, ['record' => $comment->id])
      ->assertFormSet([
        'post_id' => $comment->post_id,
        'user_id' => $comment->user_id,
        'body' => $comment->body,
      ])
      ->fillForm([
        'post_id' => $newPost->id,
        'user_id' => $newUser->id,
        'body' => 'Updated comment content',
      ])
      ->call('save')
      ->assertHasNoFormErrors();

    expect($comment->refresh())
      ->post_id->toBe($newPost->id)
      ->user_id->toBe($newUser->id)
      ->body->toBe('Updated comment content');
  });
});

// Table Tests
describe('CommentResource Table', function () {
  beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    $this->comments = Comment::factory()->count(5)->create([
      'post_id' => $this->post->id,
      'user_id' => $this->user->id,
    ]);
    $this->recentComment = Comment::factory()->create([
      'post_id' => $this->post->id,
      'user_id' => $this->user->id,
      'created_at' => now()->subDay(),
    ]);
  });

  // Column Rendering and Existence
  it('renders all columns correctly', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->assertCanRenderTableColumn('body')
      ->assertCanRenderTableColumn('user.name')
      ->assertCanRenderTableColumn('post.content')
      ->assertCanRenderTableColumn('created_at')
      ->assertTableColumnExists('body')
      ->assertTableColumnExists('user.name')
      ->assertTableColumnExists('post.content')
      ->assertTableColumnExists('created_at');
  });

  // Column Configuration
  it('has columns with correct configurations', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->assertCanRenderTableColumn('body')
      ->assertCanRenderTableColumn('user.name')
      ->assertCanRenderTableColumn('post.content')
      ->assertCanRenderTableColumn('created_at')
      ->assertTableColumnExists('body')
      ->assertTableColumnExists('user.name')
      ->assertTableColumnExists('post.content')
      ->assertTableColumnExists('created_at');
  });

  // Searching
  it('can search comments by body', function () {
    $comment = $this->comments->first();

    livewire(CommentResource\Pages\ListComments::class)
      ->searchTable($comment->body)
      ->assertCanSeeTableRecords([$comment])
      ->assertCanNotSeeTableRecords($this->comments->where('id', '!=', $comment->id));
  });

  // Sorting
  it('can sort comments by created_at', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->sortTable('created_at')
      ->assertCanSeeTableRecords($this->comments->sortBy('created_at'), inOrder: true)
      ->sortTable('created_at', 'desc')
      ->assertCanSeeTableRecords($this->comments->sortByDesc('created_at'), inOrder: true);
  });

  // Filters
  it('can filter recent comments only', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->filterTable('Recent comments only')
      ->assertCanSeeTableRecords([$this->recentComment]);
  });

  it('can filter comments by post location', function () {
    $location = collect(config('locations.options'))->keys()->first();
    $postWithLocation = Post::factory()->create([
      'user_id' => $this->user->id,
      'location' => $location
    ]);
    $commentWithLocation = Comment::factory()->create([
      'post_id' => $postWithLocation->id,
      'user_id' => $this->user->id,
    ]);

    livewire(CommentResource\Pages\ListComments::class)
      ->filterTable('By Post Location', ['location' => $location])
      ->assertCanSeeTableRecords([$commentWithLocation])
      ->assertCanNotSeeTableRecords($this->comments);
  });

  // Actions
  it('has edit and delete actions', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->assertTableActionExists('edit')
      ->assertTableActionExists('delete');
  });

  it('can delete a comment', function () {
    $comment = $this->comments->first();

    livewire(CommentResource\Pages\ListComments::class)
      ->callTableAction(DeleteAction::class, $comment);

    $this->assertModelMissing($comment);
  });

  it('has delete bulk action', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->assertTableBulkActionExists('delete');
  });

  it('can bulk delete comments', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->callTableBulkAction(DeleteBulkAction::class, $this->comments);

    $this->assertDatabaseCount('comments', 1); // Only the recentComment should remain
  });

  // Record Count
  it('shows correct number of comments', function () {
    livewire(CommentResource\Pages\ListComments::class)
      ->assertCountTableRecords(6); // 5 from comments + 1 recentComment
  });

  // Column State
  it('shows correct formatted comment body', function () {
    $comment = $this->comments->first();

    livewire(CommentResource\Pages\ListComments::class)
      ->assertTableColumnFormattedStateSet('body', Str::limit($comment->body, 50), $comment);
  });

  it('shows correct user name', function () {
    $comment = $this->comments->first();

    livewire(CommentResource\Pages\ListComments::class)
      ->assertTableColumnStateSet('user.name', $comment->user->name, $comment);
  });

  it('shows correct post content preview', function () {
    $comment = $this->comments->first();

    livewire(CommentResource\Pages\ListComments::class)
      ->assertTableColumnFormattedStateSet('post.content', Str::limit($comment->post->content, 50), $comment);
  });
});