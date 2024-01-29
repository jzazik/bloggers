<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';

    protected $guarded = ['id'];
    
    public $timestamps = false;
}
