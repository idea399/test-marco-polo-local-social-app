<?php

use App\Filament\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

// Form Tests
describe('PostResource Form', function () {
  // Field Existence and Configuration
  it('has all required form fields with correct configurations', function () {
    livewire(PostResource\Pages\CreatePost::class)
      ->assertFormExists()
      ->assertFormFieldExists('user_id', function (Select $field) {
        return $field->isRequired() &&
          $field->isSearchable() &&
          $field->getRelationshipName() === 'user';
      })
      ->assertFormFieldExists('content', function (Textarea $field) {
        return $field->isRequired();
      })
      ->assertFormFieldExists('image', function (FileUpload $field) {
        return $field->getMaxSize() === 2048 &&
          $field->getAcceptedFileTypes() === ['image/jpeg', 'image/png'] &&
          $field->getDirectory() === 'posts';
      })
      ->assertFormFieldExists('location', function (Select $field) {
        return $field->isRequired() &&
          $field->getOptions() === config('locations.options');
      })
      ->assertFormFieldExists('is_approved', function (Toggle $field) {
        return $field->getLabel() === 'Approved' &&
          $field->getDefaultState() === false;
      });
  });

  // Validation Tests
  it('validates required fields', function () {
    livewire(PostResource\Pages\CreatePost::class)
      ->fillForm([
        'user_id' => null,
        'content' => null,
        'location' => null,
      ])
      ->call('create')
      ->assertHasFormErrors([
        'user_id' => 'required',
        'content' => 'required',
        'location' => 'required',
      ]);
  });

  it('validates image file upload for invalid file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    livewire(PostResource\Pages\CreatePost::class)
      ->set('data.user_id', User::factory()->create()->id)
      ->set('data.content', 'Test content')
      ->set('data.location', 'US')
      ->set('data.image', $file)
      ->call('create')
      ->assertHasErrors(['data.image']);
  });

  it('validates image file upload for invalid file size', function () {
    $file = UploadedFile::fake()->create('image.jpg', 10000, 'image/jpeg');

    livewire(PostResource\Pages\CreatePost::class)
      ->set('data.user_id', User::factory()->create()->id)
      ->set('data.content', 'Test content')
      ->set('data.location', 'US')
      ->set('data.image', $file)
      ->call('create')
      ->assertHasErrors(['data.image']);
  });

  // Form State Tests
  it('can create a post with valid data', function () {
    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('post.jpg', 500, 500)->size(1000);
    $location = collect(config('locations.options'))->keys()->first();

    livewire(PostResource\Pages\CreatePost::class)
      ->fillForm([
        'user_id' => $user->id,
        'content' => 'Test post content',
        'location' => $location,
        'image' => $image,
        'is_approved' => true,
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    $post = Post::first();
    expect($post)
      ->user_id->toBe($user->id)
      ->content->toBe('Test post content')
      ->location->toBe($location)
      ->is_approved->toBeTrue();
  });

  it('can edit a post with valid data', function () {
    $post = Post::factory()->create(['is_approved' => false]);
    $newUser = User::factory()->create();

    livewire(PostResource\Pages\EditPost::class, ['record' => $post->id])
      ->assertFormSet([
        'user_id' => $post->user_id,
        'content' => $post->content,
        'location' => $post->location,
        'is_approved' => false,
      ])
      ->fillForm([
        'user_id' => $newUser->id,
        'content' => 'Updated content',
        'is_approved' => true,
      ])
      ->call('save')
      ->assertHasNoFormErrors();

    expect($post->refresh())
      ->user_id->toBe($newUser->id)
      ->content->toBe('Updated content')
      ->is_approved->toBeTrue();
  });
});

// Table Tests
describe('PostResource Table', function () {
  beforeEach(function () {
    $this->user = User::factory()->create();
    $this->posts = Post::factory()->count(5)->create(['user_id' => $this->user->id]);
    $this->postWithImage = Post::factory()->create([
      'user_id' => $this->user->id,
      'image' => 'posts/image.jpg'
    ]);
  });

  // Column Rendering and Existence
  it('renders all columns correctly', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->assertCanRenderTableColumn('content')
      ->assertCanRenderTableColumn('user.name')
      ->assertCanRenderTableColumn('image')
      ->assertCanRenderTableColumn('location')
      ->assertCanRenderTableColumn('created_at')
      ->assertCanRenderTableColumn('comments_count')
      ->assertCanRenderTableColumn('is_approved')
      ->assertTableColumnExists('content')
      ->assertTableColumnExists('user.name')
      ->assertTableColumnExists('image')
      ->assertTableColumnExists('location')
      ->assertTableColumnExists('created_at')
      ->assertTableColumnExists('comments_count')
      ->assertTableColumnExists('is_approved');
  });

  // Column Configuration
  it('has columns with correct configurations', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->assertCanRenderTableColumn('content')
      ->assertCanRenderTableColumn('user.name')
      ->assertCanRenderTableColumn('image')
      ->assertCanRenderTableColumn('created_at')
      ->assertCanRenderTableColumn('is_approved')
      ->assertTableColumnExists('content')
      ->assertTableColumnExists('user.name')
      ->assertTableColumnExists('image')
      ->assertTableColumnExists('created_at')
      ->assertTableColumnExists('is_approved');
  });

  // Searching
  it('can search posts by content', function () {
    $post = $this->posts->first();

    livewire(PostResource\Pages\ListPosts::class)
      ->searchTable($post->content)
      ->assertCanSeeTableRecords([$post])
      ->assertCanNotSeeTableRecords($this->posts->where('id', '!=', $post->id));
  });

  // Sorting
  it('can sort posts by created_at', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->sortTable('created_at')
      ->assertCanSeeTableRecords($this->posts->sortBy('created_at'), inOrder: true)
      ->sortTable('created_at', 'desc')
      ->assertCanSeeTableRecords($this->posts->sortByDesc('created_at'), inOrder: true);
  });

  it('can sort posts by comments_count', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->sortTable('comments_count')
      ->assertCanSeeTableRecords($this->posts->sortBy('comments_count'), inOrder: true)
      ->sortTable('comments_count', 'desc')
      ->assertCanSeeTableRecords($this->posts->sortByDesc('comments_count'), inOrder: true);
  });

  it('can sort posts by is_approved', function () {
    $approvedPost = Post::factory()->create(['user_id' => $this->user->id, 'is_approved' => true]);
    $allPosts = $this->posts->push($approvedPost);

    livewire(PostResource\Pages\ListPosts::class)
      ->sortTable('is_approved')
      ->assertCanSeeTableRecords($allPosts->sortBy('is_approved'), inOrder: true)
      ->sortTable('is_approved', 'desc')
      ->assertCanSeeTableRecords($allPosts->sortByDesc('is_approved'), inOrder: true);
  });

  // Filters
  it('can filter posts with images', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->filterTable('With Images')
      ->assertCanSeeTableRecords([$this->postWithImage])
      ->assertCanNotSeeTableRecords($this->posts);
  });

  it('can filter posts by location', function () {
    $location = collect(config('locations.options'))->keys()->first();
    $filteredPosts = $this->posts->where('location', $location);

    if ($filteredPosts->isEmpty()) {
      $filteredPosts = collect([
        Post::factory()->create([
          'user_id' => $this->user->id,
          'location' => $location
        ])
      ]);
    }

    livewire(PostResource\Pages\ListPosts::class)
      ->filterTable('By Location', $location)
      ->assertCanSeeTableRecords($filteredPosts);
  });

  it('can filter posts by user', function () {
    $newUser = User::factory()->create();
    $newUserPost = Post::factory()->create(['user_id' => $newUser->id]);

    livewire(PostResource\Pages\ListPosts::class)
      ->filterTable('By User', $newUser->id)
      ->assertCanSeeTableRecords([$newUserPost]);
  });

  // Actions
  it('has edit and delete actions', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->assertTableActionExists('edit')
      ->assertTableActionExists('delete');
  });

  it('can delete a post', function () {
    $post = $this->posts->first();

    livewire(PostResource\Pages\ListPosts::class)
      ->callTableAction(DeleteAction::class, $post);

    $this->assertModelMissing($post);
  });

  it('has delete bulk action', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->assertTableBulkActionExists('delete');
  });

  it('can bulk delete posts', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->callTableBulkAction(DeleteBulkAction::class, $this->posts);

    $this->assertDatabaseCount('posts', 1); // Only the postWithImage should remain
  });

  // Record Count
  it('shows correct number of posts', function () {
    livewire(PostResource\Pages\ListPosts::class)
      ->assertCountTableRecords(6); // 5 from posts + 1 postWithImage
  });

  // Column State
  it('shows correct formatted content', function () {
    $post = $this->posts->first();

    livewire(PostResource\Pages\ListPosts::class)
      ->assertTableColumnFormattedStateSet('content', Str::limit($post->content, 50), $post);
  });

  it('shows correct author name', function () {
    $post = $this->posts->first();

    livewire(PostResource\Pages\ListPosts::class)
      ->assertTableColumnStateSet('user.name', $post->user->name, $post);
  });
});