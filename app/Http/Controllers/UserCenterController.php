<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;

use App\Components\Utils\Constants;

use App\Models\User;

class UserCenterController extends Controller
{
    /**
     * @brief 
     * @return Response
     */
    public function fetchUsers(Request $request)
    {
        $this->validate($request, [
            'identity'           => 'required',
            'sex'                => 'required',
            'headAuth'           => 'required',
            'carAuth'            => 'required',
            'cityids'            => 'required',
            'carmodelids'        => 'required'
            ]);

        $query = User::where('user.id', '>', 0);

        if ($request->input('identity', 0) != 0) {
            $request->input('identity')==1 ? $query->where('user.driverstate', '!=', 0) : $query->where('user.driverstate', 0);
        }

        $request->input('sex', 0) != 0 ? $query->where('user.sex', $request->input('sex', 0)):'';
        $request->input('headAuth', 0) != 0 ? $query->where('user.headimgstate', $request->input('headAuth', 0)):'';
        $request->input('carAuth', 0) != 0 ? $query->where('user.carimagestate', $request->input('carAuth', 0)):'';


        $cityIds = $request->input('cityids', 0);
        if ($cityIds != 0) {
            $citys = explode(',', $cityIds);
            // 选择了全部城市,则不做处理
            if (!in_array(0, $citys)) {
                $query->whereIn('user.cityid', $citys);
            }
        }

        $brandIds = $request->input('carmodelids', 0);
        if ($brandIds != 0) {
            $brands = explode(',', $brandIds);
            if (!in_array(0, $brands)) {
                $query->join('carinfo', 'user.id', '=', 'carinfo.ownerid')
                    ->whereIn('carinfo.carmodelid', $brands);
            }
        }
        
        
        $users = $query->select('user.mobile', 'user.id')
                       ->where('user.mobile', 'not like', '999%')
                       ->get();

        $output = array('mobile'=>[], 'userid'=>[]);
        foreach ($users as $user) {
            $output['mobile'][] = $user->mobile;
            $output['userid'][] = $user->id;
        }

        return response()->clientSuccess(['mobiles'=>join("\n", $output['mobile']), 'userids'=>join("\n", $output['userid']), 'count'=>count($output['mobile'])]); 
    }
}
