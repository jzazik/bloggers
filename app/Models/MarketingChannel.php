<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingChannel extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'channel_id';
    
    protected $guarded = ['channel_id'];
}
