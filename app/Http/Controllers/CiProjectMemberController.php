<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redis;
use Cookie;
use Exception;
use RuntimeException;
use App\Exceptions\ApiException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\JobDone\JobDone;


use App\Models\Gaea\AdminUser;
use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectMember;

class CiProjectMemberController extends Controller
{
    public function fetchCiMemberByProjectId(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'project_id'    =>  'required'
        ]);
        $memberType = $request->Input('member_type');
        $query = CiProjectMember::where('project_id', '=', $request->Input('project_id'));
        if (!empty($memberType)) {
            $query->where('member_type', '=', $memberType);
        }
        $query->orderBy('member_type');
        $members = $query->get();

        $data = $members->map(function ($item) {
            return array('id'=>$item->gaea_user_id, 'name'=>$item->gaea_user_name);
        });
        return response()->clientSuccess($data);
    }/*}}}*/

    public function ciProjectMemberManager(Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);
        $action = $request->input('action');

        switch ($action) {

            case Constants::REQUEST_TYPE_ADD:
                $this->validate($request, [
                    'project_id'       => "required",
                    'selected_user_id' => "required",
                    'member_type'      => "required",
                ]);
                $ret = $this->addMember($request->Input('project_id'),$request->Input('selected_user_id'),$request->Input('member_type'));
                if ( !$ret ) {
                    return response()->clientError(-1, ["添加用户失败"]);
                } 
                return response()->clientSuccess([]);
                break;

            case Constants::REQUEST_TYPE_DELETE:
                $this->validate($request, [
                    'data_id' => "required",
                ]);
                $ret = $this->deleteMember($request->Input('data_id'));
                if ( !$ret ) {
                    return response()->clientError(-1, ["删除用户失败"]);
                } 
                return response()->clientSuccess([]);
                break;

            case Constants::REQUEST_TYPE_UPDATE:
                $this->validate($request, [
                    'data_id' => "required",
                    'opt'     => "required",
                ]);
                $opt = $request->Input('opt');
                $ret = false;
                switch ($opt) {
                    case 'disable': //禁用用户 
                        $ret = $this->disableMember($request->Input('data_id'));
                        break;
                    case 'enable': //启用用户 
                        $ret = $this->enableMember($request->Input('data_id'));
                        break;
                    default:
                        break;
                }
                if ( !$ret ) {
                    return response()->clientError(-1, ["操作失败"]);
                } 
                return response()->clientSuccess([]);

                break;

            case Constants::REQUEST_TYPE_GET:
                $data = AdminUser::select('id as id', 'nickname as name')->orderBy('username')->get();
                return response()->clientSuccess($data);
                break;

            case Constants::REQUEST_TYPE_LIST:
                $this->validate($request, [
                    'project_id'  => "required"
                ]);
                $data = CiProjectMember::listMemberByProjectId($request->Input('project_id'));
                return response()->clientSuccess($data);
                break;
        };
    }/*}}}*/

    private function addMember($projectId,$gaeaUserId,$memberType)
    {/*{{{*/
        $selectedUser    = AdminUser::where('id', '=', $gaeaUserId)->first();
        $selectedProject = CiProject::where('project_id', '=', $projectId)->first();
        if (!isset($selectedUser)) {
            return false;
        }
        if (!isset($selectedProject)) {
            return false;
        }
        $ciMember = new CiProjectMember();
        {
            $ciMember->project_id        = $selectedProject->project_id;
            $ciMember->project_name      = $selectedProject->project_name;
            $ciMember->gaea_user_id      = $selectedUser->id;
            $ciMember->gaea_user_name    = $selectedUser->username;
            $ciMember->state             = 1;
            $ciMember->member_type       = $memberType;
            $ciMember->operate_user_id   = Constants::getAdminId();
            $ciMember->operate_user_name = Constants::getAdminName();
        }
        $ciMember->save();
        return true;
    }/*}}}*/

    private function updateMember()
    {/*{{{*/
        return false;
    }/*}}}*/

    private function deleteMember($dataId)
    {/*{{{*/
        $ret = CiProjectMember::where('id', '=', $dataId)->delete();
        return $ret;
    }/*}}}*/

    private function enableMember($dataId)
    {/*{{{*/
        $ret = CiProjectMember::where('id', '=', $dataId)
            ->update(['state' => CiProjectMember::MEMBER_ENABLE]);

        return $ret;
    }/*}}}*/

    private function disableMember($dataId)
    {/*{{{*/
        $ret = CiProjectMember::where('id', '=', $dataId)
            ->update(['state' => CiProjectMember::MEMBER_DISABLE]);
        return $ret;
    }/*}}}*/
}
