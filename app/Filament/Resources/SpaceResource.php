<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpaceResource\Pages;
use App\Models\Space;
use App\SpaceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SpaceResource extends Resource
{
    protected static ?string $model = Space::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Ruimtes';

    protected static ?string $modelLabel = 'ruimte';

    protected static ?string $pluralModelLabel = 'ruimtes';

    protected static ?string $navigationGroup = 'Configuratie';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, Forms\Set $set): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Space::class, 'slug', ignoreRecord: true)
                    ->rules(['alpha_dash']),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->required()
                    ->options([
                        SpaceType::ConferenceRoom->value => 'Conferentieruimte',
                        SpaceType::Accommodation->value => 'Accommodatie',
                        SpaceType::CoWorking->value => 'Co-Working',
                        SpaceType::TherapyRoom->value => 'Therapieruimte',
                        SpaceType::Combined->value => 'Gecombineerd',
                    ])
                    ->native(false),
                Forms\Components\TextInput::make('capacity')
                    ->label('Capaciteit')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('personen'),
                Forms\Components\Textarea::make('description')
                    ->label('Beschrijving')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('features')
                    ->label('Kenmerken')
                    ->keyLabel('Kenmerk')
                    ->valueLabel('Waarde')
                    ->addActionLabel('Kenmerk toevoegen')
                    ->columnSpanFull(),
                Forms\Components\Select::make('can_combine_with')
                    ->label('Kan gecombineerd worden met')
                    ->multiple()
                    ->relationship('bookings', 'name', function (Builder $query) {
                        return $query->where('is_active', true);
                    })
                    ->getOptionLabelFromRecordUsing(fn (Space $record): string => $record->name)
                    ->options(function (?Space $record): array {
                        return Space::query()
                            ->where('is_active', true)
                            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actief')
                    ->default(true)
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sorteervolgorde')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (SpaceType $state): string => match ($state) {
                        SpaceType::ConferenceRoom => 'success',
                        SpaceType::Accommodation => 'info',
                        SpaceType::CoWorking => 'warning',
                        SpaceType::TherapyRoom => 'danger',
                        SpaceType::Combined => 'gray',
                    })
                    ->formatStateUsing(fn (SpaceType $state): string => match ($state) {
                        SpaceType::ConferenceRoom => 'Conferentieruimte',
                        SpaceType::Accommodation => 'Accommodatie',
                        SpaceType::CoWorking => 'Co-Working',
                        SpaceType::TherapyRoom => 'Therapieruimte',
                        SpaceType::Combined => 'Gecombineerd',
                    }),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capaciteit')
                    ->numeric()
                    ->sortable()
                    ->suffix(' personen'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sorteervolgorde')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        SpaceType::ConferenceRoom->value => 'Conferentieruimte',
                        SpaceType::Accommodation->value => 'Accommodatie',
                        SpaceType::CoWorking->value => 'Co-Working',
                        SpaceType::TherapyRoom->value => 'Therapieruimte',
                        SpaceType::Combined->value => 'Gecombineerd',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actief')
                    ->placeholder('Alle ruimtes')
                    ->trueLabel('Alleen actieve')
                    ->falseLabel('Alleen inactieve'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggleActive')
                        ->label('Actieve status omschakelen')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['is_active' => ! $record->is_active]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListSpaces::route('/'),
            'create' => Pages\CreateSpace::route('/create'),
            'view' => Pages\ViewSpace::route('/{record}'),
            'edit' => Pages\EditSpace::route('/{record}/edit'),
        ];
    }
}
