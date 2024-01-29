<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';

    public $timestamps = false;

    protected $primaryKey = 'transaction_id';
    
    protected $guarded = ['transaction_id'];

}
