<?php

namespace App\Models\Gaea;
use DB;

use Illuminate\Database\Eloquent\Model;

class CarNumbers extends Gaea
{
     protected $table = 'car_numbers';  

     protected $dates = ['registered_at'];
}
