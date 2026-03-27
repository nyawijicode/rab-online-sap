<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMonitor extends Model
{
    protected $table = 'project';
    public $timestamps = false;
    protected $connection = 'monitor_sales_pgsql';

    public function customer()
    {
        // project.customer_id -> customer.id (keduanya di koneksi PG yang sama)
        return $this->belongsTo(CustomerMonitor::class, 'customer_id', 'id');
    }
}
