<?php

namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class OpContentType extends Gaea
{
     protected $table = 'op_content_type';  

     public function export()
     {
         return $this;
     }
}
