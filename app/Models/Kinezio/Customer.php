<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    public $timestamps = false;

    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'customer_id',
        'customer_name',
        'phone',
        'email'
    ];

}
