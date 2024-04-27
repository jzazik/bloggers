<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingHistory extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';
    
    protected $table = 'marketing_history';

    protected $guarded = ['id'];

    public $timestamps = false;

}
