<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\BookingStatus;
use App\BookingType;
use App\PaymentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->label('Booking ID')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->prefix('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_type')
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
                        BookingType::Accommodation => 'Accommodation',
                        BookingType::CoWorking => 'Co-Working',
                        BookingType::LightTherapy => 'Light Therapy',
                        BookingType::Package => 'Package',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::Pending => 'warning',
                        BookingStatus::Confirmed => 'success',
                        BookingStatus::Cancelled => 'danger',
                        BookingStatus::Completed => 'info',
                        BookingStatus::NoShow => 'gray',
                    })
                    ->formatStateUsing(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::Pending => 'Pending',
                        BookingStatus::Confirmed => 'Confirmed',
                        BookingStatus::Cancelled => 'Cancelled',
                        BookingStatus::Completed => 'Completed',
                        BookingStatus::NoShow => 'No Show',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Refunded => 'info',
                        PaymentStatus::PartiallyRefunded => 'warning',
                    })
                    ->formatStateUsing(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'Pending',
                        PaymentStatus::Paid => 'Paid',
                        PaymentStatus::Failed => 'Failed',
                        PaymentStatus::Refunded => 'Refunded',
                        PaymentStatus::PartiallyRefunded => 'Partially Refunded',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        BookingStatus::Pending->value => 'Pending',
                        BookingStatus::Confirmed->value => 'Confirmed',
                        BookingStatus::Cancelled->value => 'Cancelled',
                        BookingStatus::Completed->value => 'Completed',
                        BookingStatus::NoShow->value => 'No Show',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        PaymentStatus::Pending->value => 'Pending',
                        PaymentStatus::Paid->value => 'Paid',
                        PaymentStatus::Failed->value => 'Failed',
                        PaymentStatus::Refunded->value => 'Refunded',
                        PaymentStatus::PartiallyRefunded->value => 'Partially Refunded',
                    ]),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.bookings.view', $record)),
            ])
            ->bulkActions([
                //
            ]);
    }
}
