<?php

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

// Form Tests
describe('UserResource Form', function () {
    // Field Existence and Configuration
    it('has all required form fields with correct configurations', function () {
        livewire(UserResource\Pages\CreateUser::class)
            ->assertFormExists()
            ->assertFormFieldExists('name', function (TextInput $field) {
                return $field->isRequired();
            })
            ->assertFormFieldExists('email', function (TextInput $field) {
                return $field->isRequired() && $field->getType() === 'email';
            })
            ->assertFormFieldExists('avatar', function (FileUpload $field) {
                return $field->getMaxSize() === 2048 &&
                    $field->getAcceptedFileTypes() === ['image/jpeg', 'image/png'] &&
                    $field->getDirectory() === 'avatars';
            })
            ->assertFormFieldExists('location', function (Select $field) {
                return $field->isRequired() &&
                    $field->getOptions() === config('locations.options');
            });
    });

    // Validation Tests
    it('validates required fields', function () {
        livewire(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => null,
                'email' => null,
                'location' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'required',
                'location' => 'required',
            ]);
    });

    it('validates email format', function () {
        livewire(UserResource\Pages\CreateUser::class)
            ->fillForm(['email' => 'invalid-email'])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email']);
    });

    it('validates avatar file upload for invalid file type', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        livewire(UserResource\Pages\CreateUser::class)
            ->set('data.name', 'Test User')
            ->set('data.email', 'user@example.com')
            ->set('data.location', 'US')
            ->set('data.avatar', $file)
            ->call('create')
            ->assertHasErrors(['data.avatar']);
    });

    it('validates avatar file upload for invalid file size', function () {
        $file = UploadedFile::fake()->create('document.jpg', 10000, 'image/jpeg');

        livewire(UserResource\Pages\CreateUser::class)
            ->set('data.name', 'Test User')
            ->set('data.email', 'user@example.com')
            ->set('data.location', 'US')
            ->set('data.avatar', $file)
            ->call('create')
            ->assertHasErrors(['data.avatar']);
    });
});

// Table Tests
describe('UserResource Table', function () {
    beforeEach(function () {
        $this->users = User::factory()->count(5)->create();
    });

    // Column Rendering
    it('renders all columns correctly', function () {
        livewire(UserResource\Pages\ListUsers::class)
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('email')
            ->assertCanRenderTableColumn('avatar')
            ->assertCanRenderTableColumn('location')
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('avatar')
            ->assertTableColumnExists('location');
    });

    // Searching
    it('can search users by name and email', function () {
        $user = $this->users->first();

        livewire(UserResource\Pages\ListUsers::class)
            ->searchTable($user->name)
            ->assertCanSeeTableRecords([$user])
            ->searchTable($user->email)
            ->assertCanSeeTableRecords([$user]);
    });

    // Sorting
    it('can sort users by name and email', function () {
        livewire(UserResource\Pages\ListUsers::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords($this->users->sortBy('name'), inOrder: true)
            ->sortTable('email', 'desc')
            ->assertCanSeeTableRecords($this->users->sortByDesc('email'), inOrder: true);
    });

    // Filters
    it('can filter users by location', function () {
        $location = collect(config('locations.options'))->keys()->first();
        $filteredUsers = $this->users->where('location', $location);;

        livewire(UserResource\Pages\ListUsers::class)
            ->filterTable('By Location', $location)
            ->assertCanSeeTableRecords($filteredUsers);
    });

    it('can filter users with posts', function () {
        $userWithPosts = User::factory()->hasPosts(3)->create();
        $usersWithoutPosts = $this->users;

        livewire(UserResource\Pages\ListUsers::class)
            ->filterTable('Has Posts')
            ->assertCanSeeTableRecords([$userWithPosts])
            ->assertCanNotSeeTableRecords($usersWithoutPosts);
    });

    // Bulk Actions
    it('has export bulk action with correct configuration', function () {
        livewire(UserResource\Pages\ListUsers::class)
            ->assertTableBulkActionExists('export');
    });

    // Single Actions
    it('has edit action', function () {
        livewire(UserResource\Pages\ListUsers::class)
            ->assertTableActionExists('edit');
    });

    // Record Count
    it('shows correct number of users', function () {
        livewire(UserResource\Pages\ListUsers::class)
            ->assertCountTableRecords(5);
    });
});