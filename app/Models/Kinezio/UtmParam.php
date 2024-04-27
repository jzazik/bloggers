<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtmParam extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'kinezio';

    protected $primaryKey = 'utm_id';
    
    protected $guarded = ['utm_id'];

}
