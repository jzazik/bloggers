<?php

namespace App\Models\Popovich;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];
}
