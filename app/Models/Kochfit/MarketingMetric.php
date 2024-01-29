<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingMetric extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';

    public $timestamps = false;

    protected $guarded = ['id'];
}
