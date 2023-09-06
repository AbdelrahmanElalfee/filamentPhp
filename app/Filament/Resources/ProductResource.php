<?php

namespace App\Filament\Resources;

use App\Enums\ProductTypeEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 20;

    protected static ?string $activeNavigationIcon = 'heroicon-o-check-badge';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'brand.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return  [
            'Brand' => $record->brand->name,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['brand']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set){
                                        if ($operation != 'create'){
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),
                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Product::class, 'slug', ignoreRecord: true),
                                MarkdownEditor::make('description')
                                    ->columnSpan('full')
                                    ->required(),
                            ])->columns(2),
                        Section::make('Pricing & Inventory')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->required(),
                                TextInput::make('price')
                                    ->numeric()
                                    ->default(0.00)
                                    ->rule('regex:/^\d{1,6}(\.\d{0,2})?$/')
                                    ->required(),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required(),
                                Select::make('type')
                                    ->options([
                                        'downloadable' => ProductTypeEnum::DOWNLOADABLE->value,
                                        'deliverable' => ProductTypeEnum::DELIVERABLE->value
                                    ])
                                    ->required(),
                            ])->columns(2),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label('Visibility')
                                    ->helperText('Enable or disable product visibility.')
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->helperText('Enable or disable product featured status.'),
                                DatePicker::make('published_at')
                                    ->label('Availability')
                                    ->default(now()),
                            ]),
                        Section::make('images')
                            ->schema([
                                FileUpload::make('image')
                                    ->columnSpan('full')
                                    ->directory('images')
                                    ->image()
                                    ->imageEditor()
                                    ->required(),
                            ])->collapsible(),
                        Section::make('Associations')
                            ->schema([
                                Select::make('brand_id')
                                    ->label('Brands')
                                    ->relationship('brand', 'name')
                                    ->required(),
                                Select::make('categories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('name')
                    ->color(color::Purple)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('categories.name')
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_visible')
                    ->boolean()
                    ->sortable()
                    ->label('Visibility')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->sortable()
                    ->label('Featured')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sku')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(50)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantity')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->placeholder('All')
                    ->boolean()
                    ->trueLabel('Visible')
                    ->falseLabel('Hidden')
                    ->native(false),
                TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All')
                    ->boolean()
                    ->trueLabel('Featured')
                    ->falseLabel('Not featured')
                    ->native(false),
                SelectFilter::make('brand')
                    ->multiple()
                    ->relationship('brand', 'name')
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
