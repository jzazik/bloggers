<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingMetric extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    public $timestamps = false;

    protected $guarded = ['id'];
}
