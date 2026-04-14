<?php

namespace App\Filament\Pickup\Widgets;

use App\Models\Pickup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestPickups extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Pickup Terbaru';

    public function table(Table $table): Table
    {
        $query = Pickup::query()->withSum('items', 'pickup_quantity');
        
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['superadmin', 'logistik', 'purchasing'])) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('cabang_pic_user_id', $user->id);
            });
        }

        return $table
            ->query($query->latest('created_at')->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'scheduled',
                        'info'    => 'shipped',
                        'success' => 'completed',
                        'danger'  => 'canceled',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Vendor')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('pickup_date')
                    ->label('Tanggal Pickup')
                    ->date('d M Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('items_sum_pickup_quantity')
                    ->label('Total Qty')
                    ->numeric(0),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('Lihat')
                    ->url(fn (Pickup $record): string => \App\Filament\Pickup\Resources\PickupResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->outlined(),
            ]);
    }
}
