<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    public $timestamps = false;

    protected $primaryKey = 'subscription_id';

    protected $guarded = ['subscription_id'];
}
