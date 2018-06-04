<?php

namespace App\Models\Gaea;

use DB;

use Carbon\Carbon;

use App\Components\Utils\Constants;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\MarketApp;

use App\Components\Utils\LogUtil;

class WorkflowParticipator extends Gaea
{
     protected $table = 'workflow_participator';  

     public function export()
     {/*{{{*/
         return $this;
     }/*}}}*/

}
