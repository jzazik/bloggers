<?php

namespace App\Models\Kinezio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $connection = 'kinezio';

    public $timestamps = false;

    protected $primaryKey = 'installment_id';

    protected $guarded = ['installment_id'];
    
}
