<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Enums\ProductTypeEnum;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Products')
                    ->tabs([
                        Tab::make('Information')
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
                        Tab::make('Pricing & Inventory')
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
                        Tab::make('Additional Information')
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
                                Select::make('brand_id')
                                    ->label('Brands')
                                    ->relationship('brand', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->required(),
                                FileUpload::make('image')
                                    ->columnSpan('full')
                                    ->directory('images')
                                    ->image()
                                    ->imageEditor()
                                    ->required(),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
