<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingChannel extends Model
{
    use HasFactory;

    protected $connection = 'kochfit';

    public $timestamps = false;

    protected $primaryKey = 'channel_id';
    
    protected $guarded = ['channel_id'];
}
