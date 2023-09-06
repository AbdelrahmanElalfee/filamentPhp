<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    protected static int $globalSearchResultsLimit = 20;

    protected static ?string $activeNavigationIcon = 'heroicon-o-check-badge';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', '=', 'processing')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', '=', 'processing')->count() < 10
            ? 'danger'
            : color::Purple;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['customer.name', 'number'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Order Details')
                        ->schema([
                            Forms\Components\TextInput::make('number')
                                ->default('OR-'.random_int(100000, 9999999))
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->preload()
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('shipping_price')
                                ->label('Shipping Price')
                                ->dehydrated()
                                ->numeric()
                                ->required(),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'pending' => OrderStatusEnum::PENDING->value,
                                    'processing' => OrderStatusEnum::PROCESSING->value,
                                    'Completed' => OrderStatusEnum::COMPLETED->value,
                                    'declined' => OrderStatusEnum::DECLINED->value,
                                ])->required(),
                            Forms\Components\MarkdownEditor::make('notes')
                                ->columnSpanFull()
                        ])->columns(2),
                    Step::make('Order Items')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->schema([
                                    Forms\Components\Select::make('product_id')
                                        ->label('Product')
                                        ->required()
                                        ->options(Product::query()->pluck('name', 'id'))
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, Forms\Set $set) =>
                                            $set('unit_price', Product::find($state)?->price ?? 0)
                                        ),
                                    Forms\Components\TextInput::make('quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->live()
                                        ->dehydrated(),
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Unit Price')
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),
                                    Forms\Components\Placeholder::make('total_price')
                                        ->label('Total Price')
                                        ->content(function($get){
                                            return $get('quantity') * $get('unit_price');
                                        })
                                ])->columns(4)
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shipping_price')
                    ->label('Shipping Price')
                    ->sortable()
                    ->searchable()
                    ->money('EUR')
//                    ->summarize([
//                        Tables\Columns\Summarizers\Sum::make()
//                            ->money()
//                    ])
                ,
                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->date()
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
