<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Exports\UserExporter;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('email')->email()->required(),
                Forms\Components\FileUpload::make('avatar')
                    ->image()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->directory('avatars')
                    ->helperText('Upload an avatar image (max size: 2MB; formats: jpeg, png).'),
                Forms\Components\Select::make('location')
                    ->options(config('locations.options'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\ImageColumn::make('avatar')->disk('public'),
                Tables\Columns\TextColumn::make('location')->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('By Location')
                    ->form([
                        Forms\Components\Select::make('location')
                            ->options(config('locations.options')),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['location'], fn($q, $location) => $q->where('location', $location))),
                Tables\Filters\Filter::make('Has Posts')
                    ->query(fn(Builder $query): Builder => $query->has('posts')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(UserExporter::class)
                    ->formats([
                        ExportFormat::Csv,
                    ])
                    ->fileName(fn(Export $export): string => "users-{$export->getKey()}"),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
