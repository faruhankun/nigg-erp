<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 
        'product_id', 
        'quantity', 
        'price', 
        'note', // Add note to fillable
    ];

    // Relationship with Sale
    public function sales()
    {
        return $this->belongsTo(Sale::class);
    }

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
