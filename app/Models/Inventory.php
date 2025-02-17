<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'warehouse_id',
		'location_id',
        'quantity',
        'reserved_quantity',
        'in_transit_quantity',
    ];

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship with Warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
	
	// Define relationship with Location
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
