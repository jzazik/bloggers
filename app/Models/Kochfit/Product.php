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
    
    const PACKAGES = [
        'пакет 1' => [
            [
                'product_name' => 'Онлайн-курс Пакет 1 Активная беременность 5',
                'product_type' => 'Активная беременность',
                'product_length' => 5,
                'product_price' => 8993,
            ],
            [
                'product_name' => 'Онлайн-курс Пакет 1 Восстановление после родов 3',
                'product_type' => 'Восстановление после родов',
                'product_length' => 3,
                'product_price' => 6997,
            ]
        ],
        'пакет 2' => [
            [
                'product_name' => 'Онлайн-курс Пакет 2 Восстановление после родов 3',
                'product_type' => 'Восстановление после родов',
                'product_length' => 3,
                'product_price' => 6993,
            ],
            [
                'product_name' => 'Онлайн-курс Пакет 2 МТД и дыхание 3',
                'product_type' => 'МТД и дыхание',
                'product_length' => 3,
                'product_price' => 6997,
            ]
        ],
        'пакет 3' => [
            [
                'product_name' => 'Онлайн-курс Пакет 3 МТД и дыхание 3',
                'product_type' => 'МТД и дыхание',
                'product_length' => 3,
                'product_price' => 7000,
            ],
            [
                'product_name' => 'Онлайн-курс Пакет 3 Красота и здоровье Стандарт 3',
                'product_type' => 'Красота и здоровье Стандарт',
                'product_length' => 3,
                'product_price' => 5790,
            ]
        ]
    ];
    
    public static function getCreateArrays($data)
    {
        $create = [
            'product_name' => $data['product_name'],
        ];
        
        unset($data['product_name']);
        
        return [
            $create,
            $data
        ];
    }
    
    public function getProducts($data)
    {
        unset($data['sale_number']);

        $products = [];
        $packageProducts = null;
        
        if (mb_strpos(mb_strtolower($data['product_name']), 'пакет 1') !== false) {
            $packageProducts = self::PACKAGES['пакет 1'];
        } else if (mb_strpos(mb_strtolower($data['product_name']), 'пакет 2') !== false) {
            $packageProducts = self::PACKAGES['пакет 2'];
        } else if (mb_strpos(mb_strtolower($data['product_name']), 'пакет 3') !== false) {
            $packageProducts = self::PACKAGES['пакет 3'];
        }
        
        if (!$packageProducts) {
            $products[] = self::updateOrCreate(...self::getCreateArrays($data));
        }

        
        if ($packageProducts) {
            foreach ($packageProducts as $productData) {

                $newProductData = $data;

                foreach ($productData as $key => $value) {
                    $newProductData[$key] = $value;
                }

                $products[] = self::updateOrCreate(...self::getCreateArrays($newProductData));
            }
        }
        
        
        return $products;
    }
}
