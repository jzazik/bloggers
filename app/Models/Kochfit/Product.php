<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';

    public $timestamps = false;
    
    protected $primaryKey = 'product_id';
    
    protected $guarded = ['product_id'];
}
