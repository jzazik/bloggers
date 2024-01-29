<?php

namespace App\Models\Popovich;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    
    public $timestamps = false;
}
