<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'customer_id',
        'customer_name',
        'phone',
        'email'
    ];

}
