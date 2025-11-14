<?php

namespace App\Filament\Resources;

use App\BookingStatus;
use App\BookingType;
use App\DurationType;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\PricingRule;
use App\Models\Space;
use App\PaymentStatus;
use App\SpaceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Boekingen';

    protected static ?string $modelLabel = 'boeking';

    protected static ?string $pluralModelLabel = 'boekingen';

    protected static ?string $navigationGroup = 'Boekingen';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Klantinformatie')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Klant')
                            ->relationship(
                                name: 'customer',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('name')
                            )
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => "{$record->name} ({$record->email})")
                            ->required()
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Naam')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Customer::class, 'email'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefoon')
                                    ->tel()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('company')
                                    ->label('Bedrijf')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('address')
                                    ->label('Adres')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Boekingsdetails')
                    ->schema([
                        Forms\Components\Select::make('booking_type')
                            ->label('Boekingstype')
                            ->required()
                            ->options([
                                BookingType::Conferentie->value => 'Conferentie',
                                BookingType::Accommodation->value => 'Accommodatie',
                                BookingType::CoWorking->value => 'Co-Working',
                                BookingType::LightTherapy->value => 'Lichttherapie',
                                BookingType::Package->value => 'Pakket',
                            ])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                // Clear space selection when booking type changes
                                $set('space_id', null);
                                $set('duration_type', null);
                                $set('price', null);
                                $set('final_price', '0.00');
                            }),
                        Forms\Components\Select::make('space_id')
                            ->label('Ruimte')
                            ->options(function (Get $get): array {
                                $bookingType = $get('booking_type');

                                // If no booking type selected, return empty array
                                if (! $bookingType) {
                                    return [];
                                }

                                // Map booking type to space types
                                $spaceTypes = match ($bookingType) {
                                    BookingType::Conferentie->value => [SpaceType::ConferenceRoom, SpaceType::Combined],
                                    BookingType::Accommodation->value => [SpaceType::Accommodation],
                                    BookingType::CoWorking->value => [SpaceType::CoWorking],
                                    BookingType::LightTherapy->value => [SpaceType::TherapyRoom],
                                    BookingType::Package->value => [
                                        SpaceType::ConferenceRoom,
                                        SpaceType::Accommodation,
                                        SpaceType::TherapyRoom,
                                        SpaceType::Combined,
                                    ],
                                    default => [],
                                };

                                return Space::query()
                                    ->active()
                                    ->whereIn('type', $spaceTypes)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Space $space): array => [
                                        $space->id => "{$space->name} ({$space->capacity} people)",
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                // Clear duration type and price when space changes
                                $set('duration_type', null);
                                $set('price', null);
                                self::updateFinalPrice($get, $set);
                            }),
                        Forms\Components\Select::make('duration_type')
                            ->label('Duurtype')
                            ->required()
                            ->options(function (Get $get): array {
                                $spaceId = $get('space_id');

                                // If no space selected, return empty array to disable select
                                if (! $spaceId) {
                                    return [];
                                }

                                // Get all active pricing rules for the selected space
                                $durationTypes = PricingRule::where('space_id', $spaceId)
                                    ->active()
                                    ->pluck('duration_type')
                                    ->unique()
                                    ->mapWithKeys(fn (DurationType $type): array => [
                                        $type->value => match ($type) {
                                            DurationType::HalfDay => 'Halve Dag',
                                            DurationType::FullDay => 'Hele Dag',
                                            DurationType::Night => 'Nacht',
                                            DurationType::Session => 'Sessie',
                                            DurationType::DayPass => 'Dagpas',
                                            DurationType::Weekly => 'Wekelijks',
                                            DurationType::Monthly => 'Maandelijks',
                                            DurationType::Quarterly => 'Kwartaal',
                                        },
                                    ])
                                    ->toArray();

                                // If no pricing rules found, return all duration types as fallback
                                if (empty($durationTypes)) {
                                    return [
                                        DurationType::HalfDay->value => 'Halve Dag',
                                        DurationType::FullDay->value => 'Hele Dag',
                                        DurationType::Night->value => 'Nacht',
                                        DurationType::Session->value => 'Sessie',
                                        DurationType::DayPass->value => 'Dagpas',
                                        DurationType::Weekly->value => 'Wekelijks',
                                        DurationType::Monthly->value => 'Maandelijks',
                                        DurationType::Quarterly->value => 'Kwartaal',
                                    ];
                                }

                                return $durationTypes;
                            })
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                // Update price based on selected space and duration type
                                if ($state) {
                                    $spaceId = $get('space_id');
                                    if ($spaceId) {
                                        $pricingRule = PricingRule::where('space_id', $spaceId)
                                            ->where('duration_type', $state)
                                            ->active()
                                            ->first();

                                        if ($pricingRule) {
                                            $set('price', $pricingRule->price);
                                        }
                                    }
                                }
                                self::updateFinalPrice($get, $set);
                            }),
                        Forms\Components\TextInput::make('price')
                            ->label('Prijs')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::updateFinalPrice($get, $set);
                            }),
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Startdatum')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Einddatum')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->afterOrEqual(function (Get $get): ?string {
                                // For single-day bookings, allow same-day (afterOrEqual)
                                // For multi-day bookings, require strictly after
                                $durationType = $get('duration_type');
                                $singleDayTypes = [
                                    DurationType::DayPass->value,
                                    DurationType::HalfDay->value,
                                    DurationType::FullDay->value,
                                    DurationType::Session->value,
                                ];

                                // Allow same-day for single-day duration types
                                if ($durationType && in_array($durationType, $singleDayTypes)) {
                                    return 'start_date';
                                }

                                // For multi-day types or when no duration selected,
                                // still use afterOrEqual (will be validated elsewhere if needed)
                                return 'start_date';
                            }),
                        Forms\Components\TextInput::make('number_of_people')
                            ->label('Aantal personen')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Prijzen')
                    ->schema([
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Kortingsbedrag')
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0)
                            ->default(0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::updateFinalPrice($get, $set);
                            }),
                        Forms\Components\TextInput::make('final_price')
                            ->label('Eindprijs')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->readOnly()
                            ->default(0)
                            ->helperText('Prijs na korting'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Betaling')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                BookingStatus::Pending->value => 'In afwachting',
                                BookingStatus::Confirmed->value => 'Bevestigd',
                                BookingStatus::Cancelled->value => 'Geannuleerd',
                                BookingStatus::Completed->value => 'Voltooid',
                                BookingStatus::NoShow->value => 'Niet verschenen',
                            ])
                            ->default(BookingStatus::Pending->value)
                            ->native(false),
                        Forms\Components\Select::make('payment_status')
                            ->label('Betalingsstatus')
                            ->required()
                            ->options([
                                PaymentStatus::Pending->value => 'In afwachting',
                                PaymentStatus::Paid->value => 'Betaald',
                                PaymentStatus::Failed->value => 'Mislukt',
                                PaymentStatus::Refunded->value => 'Terugbetaald',
                                PaymentStatus::PartiallyRefunded->value => 'Gedeeltelijk terugbetaald',
                            ])
                            ->default(PaymentStatus::Pending->value)
                            ->native(false),
                        Forms\Components\TextInput::make('mollie_payment_id')
                            ->label('Mollie Betalings-ID')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Aanvullende Informatie')
                    ->schema([
                        Forms\Components\Textarea::make('special_requests')
                            ->label('Speciale Verzoeken')
                            ->rows(3)
                            ->placeholder('Speciale verzoeken van de klant')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Interne Notities')
                            ->rows(3)
                            ->placeholder('Interne notities (niet zichtbaar voor klant)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function updateFinalPrice(Get $get, Set $set): void
    {
        $price = (float) ($get('price') ?? 0);
        $discountAmount = (float) ($get('discount_amount') ?? 0);
        $finalPrice = max(0, $price - $discountAmount);
        $set('final_price', number_format($finalPrice, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Klant')
                    ->searchable(['customers.name', 'customers.email'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_type')
                    ->label('Boekingstype')
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
                Tables\Columns\TextColumn::make('space.name')
                    ->label('Ruimte')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_type')
                    ->label('Duurtype')
                    ->formatStateUsing(fn (?DurationType $state): string => $state ? match ($state) {
                        DurationType::HalfDay => 'Halve Dag',
                        DurationType::FullDay => 'Hele Dag',
                        DurationType::Night => 'Nacht',
                        DurationType::Session => 'Sessie',
                        DurationType::DayPass => 'Dagpas',
                        DurationType::Weekly => 'Wekelijks',
                        DurationType::Monthly => 'Maandelijks',
                        DurationType::Quarterly => 'Kwartaal',
                    } : '-')
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
                        BookingStatus::Pending => 'In afwachting',
                        BookingStatus::Confirmed => 'Bevestigd',
                        BookingStatus::Cancelled => 'Geannuleerd',
                        BookingStatus::Completed => 'Voltooid',
                        BookingStatus::NoShow => 'Niet verschenen',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Einddatum')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_people')
                    ->label('Aantal personen')
                    ->numeric()
                    ->suffix(' personen')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Eindprijs')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Betalingsstatus')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Refunded => 'info',
                        PaymentStatus::PartiallyRefunded => 'warning',
                    })
                    ->formatStateUsing(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'In afwachting',
                        PaymentStatus::Paid => 'Betaald',
                        PaymentStatus::Failed => 'Mislukt',
                        PaymentStatus::Refunded => 'Terugbetaald',
                        PaymentStatus::PartiallyRefunded => 'Gedeeltelijk terugbetaald',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        BookingStatus::Pending->value => 'In afwachting',
                        BookingStatus::Confirmed->value => 'Bevestigd',
                        BookingStatus::Cancelled->value => 'Geannuleerd',
                        BookingStatus::Completed->value => 'Voltooid',
                        BookingStatus::NoShow->value => 'Niet verschenen',
                    ]),
                Tables\Filters\SelectFilter::make('booking_type')
                    ->label('Boekingstype')
                    ->options([
                        BookingType::Conferentie->value => 'Conferentie',
                        BookingType::Accommodation->value => 'Accommodatie',
                        BookingType::CoWorking->value => 'Co-Working',
                        BookingType::LightTherapy->value => 'Lichttherapie',
                        BookingType::Package->value => 'Pakket',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Betalingsstatus')
                    ->options([
                        PaymentStatus::Pending->value => 'In afwachting',
                        PaymentStatus::Paid->value => 'Betaald',
                        PaymentStatus::Failed->value => 'Mislukt',
                        PaymentStatus::Refunded->value => 'Terugbetaald',
                        PaymentStatus::PartiallyRefunded->value => 'Gedeeltelijk terugbetaald',
                    ]),
                Tables\Filters\Filter::make('start_date')
                    ->label('Startdatum')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Van')
                            ->native(false),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Tot')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('customer')
                    ->label('Klant')
                    ->relationship('customer', 'name')
                    ->searchable(['name', 'email'])
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make()->button(),
                Tables\Actions\EditAction::make()->button(),
                Tables\Actions\Action::make('confirm')
                    ->label('Bevestigen')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->status === BookingStatus::Pending)
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => $record->confirm())
                    ->successNotificationTitle('Boeking bevestigd')
                    ->button(),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuleren')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Booking $record): bool => ! in_array($record->status, [BookingStatus::Cancelled, BookingStatus::Completed]))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reden voor annulering')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Booking $record, array $data): void {
                        $record->cancel($data['reason']);
                    })
                    ->successNotificationTitle('Boeking geannuleerd')
                    ->button(),
            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                    Tables\Actions\RestoreBulkAction::make(),
//                    Tables\Actions\ForceDeleteBulkAction::make(),
//                ]),
            ])
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
