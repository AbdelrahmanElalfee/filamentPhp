<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('number')
                                    ->default('OR-'.random_int(100000, 9999999))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                TextInput::make('shipping_price')
                                    ->label('Shipping Price')
                                    ->dehydrated()
                                    ->numeric()
                                    ->required(),
                                Select::make('type')
                                    ->options([
                                        'pending' => OrderStatusEnum::PENDING->value,
                                        'processing' => OrderStatusEnum::PROCESSING->value,
                                        'Completed' => OrderStatusEnum::COMPLETED->value,
                                        'declined' => OrderStatusEnum::DECLINED->value,
                                    ])->default('pending')
                                    ->required(),
                                MarkdownEditor::make('notes')
                                    ->columnSpanFull(),
                                ])->columns(2),
                        Section::make('Order Items')
                            ->schema([
                                Repeater::make('items')
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Product')
                                            ->required()
                                            ->relationship('items', 'name')
                                            ->reactive()
                                            ->afterStateUpdated(fn($state, Set $set) =>
                                                $set('unit_price', Product::find($state)?->price ?? 0)
                                            ),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live()
                                            ->dehydrated(),
                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->disabled()
                                            ->dehydrated()
                                            ->required(),
                                        Placeholder::make('total_price')
                                            ->label('Total Price')
                                            ->content(function($get){
                                                return $get('quantity') * $get('unit_price');
                                            })
                                    ])->columns(4),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Placeholder::make('created_at')
                                    ->content(function($get){
                                        $date = Carbon::parse($get('created_at'));
                                        $now = Carbon::now();
                                        return str_replace('before', 'ago', $date->diffForHumans($now));
                                    }),
                                Placeholder::make('updated_at')
                                    ->label('Last modified at')
                                    ->content(function ($get){
                                        $date = Carbon::parse($get('updated_at'));
                                        $now = Carbon::now();
                                        return str_replace('before', 'ago', $date->diffForHumans($now));
                                    }),
                            ]),
                    ]),
            ]);
    }

}
