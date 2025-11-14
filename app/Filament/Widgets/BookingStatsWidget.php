<?php

namespace App\Filament\Widgets;

use App\BookingStatus;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $endOfToday = now()->endOfDay();

        $todayBookings = Booking::query()
            ->whereBetween('start_date', [$today, $endOfToday])
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Pending])
            ->count();

        $upcomingBookings = Booking::query()
            ->where('start_date', '>', now())
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Pending])
            ->count();

        $pendingBookings = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->where('start_date', '>=', now())
            ->count();

        $todayRevenue = Booking::query()
            ->whereBetween('start_date', [$today, $endOfToday])
            ->where('status', BookingStatus::Confirmed)
            ->sum('final_price');

        return [
            Stat::make('Boekingen Vandaag', $todayBookings)
                ->description('Boekingen die vandaag starten')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('success'),

            Stat::make('Aankomende Boekingen', $upcomingBookings)
                ->description('Toekomstige boekingen')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),

            Stat::make('In Afwachting', $pendingBookings)
                ->description('Wachten op bevestiging')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('warning'),

            Stat::make('Omzet Vandaag', '€ '.number_format($todayRevenue, 2, ',', '.'))
                ->description('Bevestigde boekingen vandaag')
                ->descriptionIcon('heroicon-o-currency-euro')
                ->color('success'),
        ];
    }
}
