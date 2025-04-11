<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('post_id')
                    ->label('Post')
                    ->relationship('post', 'content')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('body')
                    ->label('Comment Text')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('body')
                    ->label('Comment')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),
                Tables\Columns\TextColumn::make('post.content')
                    ->label('Post')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Filter comments created within the last 7 days.
                Tables\Filters\Filter::make('Recent comments only')
                    ->query(fn(Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
                // (Optional) Filter comments based on the post’s location.
                Tables\Filters\Filter::make('By Post Location')
                    ->form([
                        Forms\Components\Select::make('location')
                            ->label('Location')
                            ->options(config('locations.options')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['location'])) {
                            return $query;
                        }
                        return $query->whereHas('post', fn(Builder $q) => $q->where('location', $data['location']));
                    }),
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
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
