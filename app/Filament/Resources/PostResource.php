<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Author')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('content')->required(),
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->directory('posts')
                    ->helperText('Upload an avatar image (max size: 2MB; formats: jpeg, png).'),
                Forms\Components\Select::make('location')
                    ->options(config('locations.options'))
                    ->required(),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Author'),
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->label('Image'),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label("Created At")
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Comments Count')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_approved')
                    ->label('Is Approved')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('With Images')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('image')),
                Tables\Filters\Filter::make('By Location')
                    ->form([
                        Forms\Components\Select::make('location')
                            ->options(config('locations.options')),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['location'], fn($q, $location) => $q->where('location', $location))),
                Tables\Filters\Filter::make('By User')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['user_id'], fn($q, $userId) => $q->where('user_id', $userId))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
