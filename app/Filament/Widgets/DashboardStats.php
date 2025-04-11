<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', format_number(User::count())),
            Stat::make('Total Posts', format_number(Post::count())),
            Stat::make('Total Comments', format_number(Comment::count())),
        ];
    }
}
