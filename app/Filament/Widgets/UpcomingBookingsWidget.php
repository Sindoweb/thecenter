<?php

namespace App\Filament\Widgets;

use App\BookingStatus;
use App\BookingType;
use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['customer', 'space'])
                    ->where('start_date', '>=', now())
                    ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                    ->orderBy('start_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Klant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('space.name')
                    ->label('Ruimte')
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (BookingType $state): string => match ($state) {
                        BookingType::Conferentie => 'success',
                        BookingType::Accommodation => 'info',
                        BookingType::CoWorking => 'warning',
                        BookingType::LightTherapy => 'danger',
                        BookingType::Package => 'gray',
                    })
                    ->formatStateUsing(fn (BookingType $state): string => match ($state) {
                        BookingType::Conferentie => 'Conferentie',
                        BookingType::Accommodation => 'Accommodatie',
                        BookingType::CoWorking => 'Co-Working',
                        BookingType::LightTherapy => 'Lichttherapie',
                        BookingType::Package => 'Pakket',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Einddatum')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::Pending => 'warning',
                        BookingStatus::Confirmed => 'success',
                        BookingStatus::Cancelled => 'danger',
                        BookingStatus::Completed => 'info',
                        BookingStatus::NoShow => 'gray',
                    })
                    ->formatStateUsing(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::Pending => 'In Afwachting',
                        BookingStatus::Confirmed => 'Bevestigd',
                        BookingStatus::Cancelled => 'Geannuleerd',
                        BookingStatus::Completed => 'Voltooid',
                        BookingStatus::NoShow => 'Niet Verschenen',
                    }),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Prijs')
                    ->money('EUR')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Bekijken')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Booking $record): string => route('filament.admin.resources.bookings.view', $record)),
            ]);
    }

    protected function getTableHeading(): ?string
    {
        return 'Aankomende Boekingen';
    }
}
