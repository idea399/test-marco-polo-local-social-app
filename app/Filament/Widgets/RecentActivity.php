<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Post;

class RecentActivity extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->where('created_at', '>=', now()->subDay())
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('dummy')
                    ->label('Activity')
                    ->getStateUsing(fn($record) => 'Post created')
                    ->searchable(),
                Tables\Columns\TextColumn::make('content')->label('Content')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Author')->searchable(),
                Tables\Columns\TextColumn::make('location')->label('Location'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime()->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('By Location')
                    ->form([
                        Forms\Components\Select::make('location')
                            ->options(config('locations.options')),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['location'], fn($q, $location) => $q->where('location', $location))),
            ]);
    }
}
