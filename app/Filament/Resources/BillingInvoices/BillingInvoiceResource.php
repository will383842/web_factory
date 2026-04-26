<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingInvoices;

use App\Filament\Resources\BillingInvoices\Pages\ListBillingInvoices;
use App\Filament\Resources\BillingInvoices\Tables\BillingInvoicesTable;
use App\Models\BillingInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BillingInvoiceResource extends Resource
{
    protected static ?string $model = BillingInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Invoices';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return BillingInvoicesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingInvoices::route('/'),
        ];
    }
}
