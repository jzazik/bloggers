<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';

    public $timestamps = false;

    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'customer_id',
        'customer_name',
        'phone',
        'email'
    ];

}
