<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Klanten';

    protected static ?string $modelLabel = 'klant';

    protected static ?string $pluralModelLabel = 'klanten';

    protected static ?string $navigationGroup = 'Boekingen';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(Customer::class, 'email', ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefoon')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('company')
                    ->label('Bedrijf')
                    ->maxLength(255),
                Forms\Components\TextInput::make('vat_number')
                    ->label('BTW-nummer')
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Adres')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notities')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Interne notities over deze klant'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actief')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Naam'),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefoon')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('company')
                    ->label('Bedrijf')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vat_number')
                    ->label('BTW-nummer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actief')
                    ->placeholder('Alle klanten')
                    ->trueLabel('Alleen actieve')
                    ->falseLabel('Alleen inactieve'),
                Tables\Filters\Filter::make('has_active_subscriptions')
                    ->label('Heeft actieve abonnementen')
                    ->query(fn (Builder $query): Builder => $query->whereHas('subscriptions', function ($query) {
                        $query->where('starts_at', '<=', now())
                            ->where('ends_at', '>=', now())
                            ->whereNull('cancelled_at');
                    })),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->button(),
                Tables\Actions\DeleteAction::make()->button(),
            ])
            ->bulkActions([
                //                Tables\Actions\BulkActionGroup::make([
                //                    Tables\Actions\DeleteBulkAction::make(),
                //                    Tables\Actions\RestoreBulkAction::make(),
                //                    Tables\Actions\ForceDeleteBulkAction::make(),
                //                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
            RelationManagers\SubscriptionsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
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
