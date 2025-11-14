<?php

namespace App\Filament\Widgets;

use App\BookingStatus;
use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingsChart extends ChartWidget
{
    protected static ?string $heading = 'Boekingen - Afgelopen 30 Dagen';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = $this->getBookingsPerDay();

        return [
            'datasets' => [
                [
                    'label' => 'Boekingen',
                    'data' => $data['bookingsPerDay'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getBookingsPerDay(): array
    {
        $now = now();
        $bookingsPerDay = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            $count = Booking::query()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Pending])
                ->count();

            $bookingsPerDay[] = $count;
            $labels[] = $date->format('d M');
        }

        return [
            'bookingsPerDay' => $bookingsPerDay,
            'labels' => $labels,
        ];
    }
}
