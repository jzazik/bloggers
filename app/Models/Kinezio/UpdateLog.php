<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    protected $guarded = ['id'];
    
    public $timestamps = false;
}
