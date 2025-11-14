<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\BookingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

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
                Tables\Columns\TextColumn::make('booking_type')
                    ->badge()
                    ->formatStateUsing(fn (BookingType $state): string => match ($state) {
                        BookingType::Conferentie => 'Conferentie',
                        BookingType::Accommodation => 'Accommodation',
                        BookingType::CoWorking => 'Co-Working',
                        BookingType::LightTherapy => 'Light Therapy',
                        BookingType::Package => 'Package',
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_usage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_usage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(function ($record): bool {
                        return $record->starts_at <= now()
                            && $record->ends_at >= now()
                            && $record->cancelled_at === null;
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active subscriptions')
                    ->query(fn ($query) => $query
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>=', now())
                        ->whereNull('cancelled_at')),
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
