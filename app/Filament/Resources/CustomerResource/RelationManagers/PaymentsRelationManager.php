<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\PaymentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
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
                Tables\Columns\TextColumn::make('booking.id')
                    ->label('Booking')
                    ->prefix('#')
                    ->url(fn ($record) => $record->booking_id ? route('filament.admin.resources.bookings.view', $record->booking_id) : null)
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('payment_method')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
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
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
