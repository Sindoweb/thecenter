<?php

namespace App\Filament\Resources;

use App\BookingType;
use App\DurationType;
use App\Filament\Resources\PricingRuleResource\Pages;
use App\Models\PricingRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Prijsregels';

    protected static ?string $modelLabel = 'prijsregel';

    protected static ?string $pluralModelLabel = 'prijsregels';

    protected static ?string $navigationGroup = 'Configuratie';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('space_id')
                    ->label('Ruimte')
                    ->relationship('space', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('booking_type')
                    ->label('Boekingstype')
                    ->required()
                    ->options([
                        BookingType::Conferentie->value => 'Conferentie',
                        BookingType::Accommodation->value => 'Accommodation',
                        BookingType::CoWorking->value => 'Co-Working',
                        BookingType::LightTherapy->value => 'Light Therapy',
                        BookingType::Package->value => 'Package',
                    ])
                    ->native(false),
                Forms\Components\Select::make('duration_type')
                    ->label('Duurtype')
                    ->required()
                    ->options([
                        DurationType::HalfDay->value => 'Halve Dag',
                        DurationType::FullDay->value => 'Hele Dag',
                        DurationType::Night->value => 'Nacht',
                        DurationType::Session->value => 'Sessie',
                        DurationType::DayPass->value => 'Dagpas',
                        DurationType::Weekly->value => 'Wekelijks',
                        DurationType::Monthly->value => 'Maandelijks',
                        DurationType::Quarterly->value => 'Kwartaal',
                    ])
                    ->native(false)
                    ->live(),
                Forms\Components\Select::make('base_duration_type')
                    ->label('Basis duurtype (voor abonnementen)')
                    ->options([
                        DurationType::HalfDay->value => 'Halve Dag',
                        DurationType::FullDay->value => 'Hele Dag',
                    ])
                    ->native(false)
                    ->visible(fn (Forms\Get $get) => $get('duration_type') === DurationType::Quarterly->value)
                    ->helperText('Selecteer welke duur (halve of hele dag) deze kwartaalprijs betreft'),
                Forms\Components\TextInput::make('price')
                    ->label('Prijs')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->minValue(0)
                    ->step(0.01)
                    ->maxLength(10),
                Forms\Components\TextInput::make('min_people')
                    ->numeric()
                    ->minValue(1)
                    ->label('Minimum personen')
                    ->placeholder('Optioneel'),
                Forms\Components\TextInput::make('max_people')
                    ->numeric()
                    ->minValue(1)
                    ->label('Maximum personen')
                    ->placeholder('Optioneel')
                    ->gte('min_people'),
                Forms\Components\TextInput::make('discount_percentage')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->label('Korting')
                    ->placeholder('Optioneel'),
                Forms\Components\DatePicker::make('valid_from')
                    ->label('Geldig van')
                    ->native(false),
                Forms\Components\DatePicker::make('valid_until')
                    ->label('Geldig tot')
                    ->native(false)
                    ->after('valid_from'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actief')
                    ->default(true)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Beschrijving')
                    ->rows(3)
                    ->placeholder('Optionele notities of beschrijving')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('space.name')
                    ->searchable()
                    ->label('Ruimte')
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
                        BookingType::Accommodation => 'Accommodation',
                        BookingType::CoWorking => 'Co-Working',
                        BookingType::LightTherapy => 'Light Therapy',
                        BookingType::Package => 'Package',
                    }),
                Tables\Columns\TextColumn::make('duration_type')
                    ->label('Duurtype')
                    ->badge()
                    ->formatStateUsing(fn (DurationType $state): string => match ($state) {
                        DurationType::HalfDay => 'Halve Dag',
                        DurationType::FullDay => 'Hele Dag',
                        DurationType::Night => 'Nacht',
                        DurationType::Session => 'Sessie',
                        DurationType::DayPass => 'Dagpas',
                        DurationType::Weekly => 'Wekelijks',
                        DurationType::Monthly => 'Maandelijks',
                        DurationType::Quarterly => 'Kwartaal',
                    }),
                Tables\Columns\TextColumn::make('base_duration_type')
                    ->label('Basis duur')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?DurationType $state): string => $state ? match ($state) {
                        DurationType::HalfDay => 'Halve Dag',
                        DurationType::FullDay => 'Hele Dag',
                        default => '-',
                    } : '-')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Prijs')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Korting')
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Geldig van')
                    ->date()
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Geldig tot')
                    ->date()
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('space')
                    ->label('Ruimte')
                    ->relationship('space', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('booking_type')
                    ->label('Boekingstype')
                    ->options([
                        BookingType::Conferentie->value => 'Conferentie',
                        BookingType::Accommodation->value => 'Accommodation',
                        BookingType::CoWorking->value => 'Co-Working',
                        BookingType::LightTherapy->value => 'Light Therapy',
                        BookingType::Package->value => 'Package',
                    ]),
                Tables\Filters\SelectFilter::make('duration_type')
                    ->label('Duurtype')
                    ->options([
                        DurationType::HalfDay->value => 'Halve Dag',
                        DurationType::FullDay->value => 'Hele Dag',
                        DurationType::Night->value => 'Nacht',
                        DurationType::Session->value => 'Sessie',
                        DurationType::DayPass->value => 'Dagpas',
                        DurationType::Weekly->value => 'Wekelijks',
                        DurationType::Monthly->value => 'Maandelijks',
                        DurationType::Quarterly->value => 'Kwartaal',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actief')
                    ->placeholder('Alle regels')
                    ->trueLabel('Alleen actieve')
                    ->falseLabel('Alleen inactieve'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }
}
