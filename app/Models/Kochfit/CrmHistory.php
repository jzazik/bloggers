<?php

namespace App\Models\Kochfit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmHistory extends Model
{
    use HasFactory;
    
    protected $connection = 'kochfit';

    protected $table = 'crm_history';
    
    protected $guarded = ['id'];

    public $timestamps = false;


}
