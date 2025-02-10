<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    public $timestamps = false;
    
    protected $primaryKey = 'product_id';
    
    protected $guarded = ['product_id'];

    public function getProducts($data)
    {
        $products = [];
        
        if (mb_strpos(mb_strtolower($data['product_name']), 'тренировки') !== false && mb_strpos(mb_strtolower($data['product_name']), 'анатомия') !== false) {
            $data['product_name'] = 'Онлайн-курс Анатомия движения';
            $data['product_price'] = 7000;
        }

        $products[] = self::firstOrCreate($data);

        return $products;
    }
}
