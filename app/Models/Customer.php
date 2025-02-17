<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'address',
        'status',
        'notes',
    ];

    
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
