<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtmParam extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'kochfit';

    protected $primaryKey = 'utm_id';
    
    protected $guarded = ['utm_id'];

}
