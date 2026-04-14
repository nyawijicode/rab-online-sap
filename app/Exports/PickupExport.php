<?php

namespace App\Exports;

use App\Exports\Sheets\PickupMainSheet;
use App\Models\Pickup;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PickupExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        // We rely on the Resource's getEloquentQuery logic for security.
        // However, for Export All, we can just start with the base query 
        // and let the caller handle visibility if needed, or re-apply same logic here.
        
        $query = Pickup::query()
            ->with(['perusahaan', 'creator', 'updater', 'items'])
            ->withSum('items', 'pickup_quantity');
        
        // Re-applying visibility logic for consistency
        if (!auth()->user()->hasAnyRole(['superadmin', 'logistik', 'purchasing'])) {
            $query->where(function ($q) {
                $q->where('created_by', auth()->id())
                  ->orWhere('cabang_pic_user_id', auth()->id());
            });
        }

        $data = $query->latest('id')->get();

        return [
            new PickupMainSheet($data, 'Data Semua Pickup'),
        ];
    }
}
