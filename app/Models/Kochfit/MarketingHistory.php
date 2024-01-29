<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingHistory extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';
    
    protected $table = 'marketing_history';

}
