<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingChannel extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    public $timestamps = false;

    protected $primaryKey = 'channel_id';
    
    protected $guarded = ['channel_id'];
}
