<?php

namespace App\Models\Popovich;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $primaryKey = 'product_id';
    
    protected $guarded = ['product_id'];

    public function getProducts($data)
    {
        $products = [];

        $products[] = self::firstOrCreate($data);

        return $products;
    }
}
