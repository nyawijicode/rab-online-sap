<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pickup_items', 'po_quantity')) {
            // biasanya decimal/double -> biar aman pakai statement
            DB::statement('ALTER TABLE `pickup_items` MODIFY `po_quantity` DECIMAL(18,6) NULL');
        }

        if (Schema::hasColumn('pickup_items', 'line_num')) {
            DB::statement('ALTER TABLE `pickup_items` MODIFY `line_num` INT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pickup_items', 'po_quantity')) {
            DB::statement('ALTER TABLE `pickup_items` MODIFY `po_quantity` DECIMAL(18,6) NOT NULL');
        }

        if (Schema::hasColumn('pickup_items', 'line_num')) {
            DB::statement('ALTER TABLE `pickup_items` MODIFY `line_num` INT NOT NULL');
        }
    }
};
