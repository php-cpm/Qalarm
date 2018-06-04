<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Exception;
use RuntimeException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;

use App\Models\Gaea\OpNotice;
use App\Models\User;

use App\Jobs\NoticeManageJob;

class NoticeController extends Controller
{
    /**
     * 添加通知任务
     * @return Response
     */
    public function addNotice(Request $request)
    {
        $this->validate($request, [
            'push_type'   => 'required | in:1,2,3',
            'push_time'   => 'required',
            'push_way'    => 'required | in:1,2',
            'push_content'=> 'required',
            'users'       => 'required',
            'user_count'  => 'required',

            'active_id'   => 'required_if:push_type,'.OpNotice::NOTICE_TYPE_ACTIVE,
            'jump_url'    => 'required_if:push_type,'.OpNotice::NOTICE_TYPE_JUMP,
            'sms_channel' => 'required_if:push_way,2 | in:1,2',
        ]);

        // 如果userids为空，则需要查询用户id
        $userIds = $request->input('userids', '');
        if ($userIds == '') {
            $mobileArr = explode("\n", $request->input('users'));
            $users = User::select('id')
                ->whereIn('mobile', $mobileArr)
                ->get();
            $arr = array();
            foreach ($users as $user) {
                $arr[] = $user->id;
            }
            $userIds = join(',', $arr);
        }

        DB::connection('gaea')->beginTransaction();

        try {
            $notice = new OpNotice();
            {
                $notice->notice_type       = $request->input('push_type');
                $notice->active_id         = $request->input('active_id', '');
                $notice->notice_link       = $request->input('jump_url');
                $notice->notice_time       = $request->input('push_time');
                $notice->notice_way        = $request->input('push_way');
                $notice->sms_channel       = $request->input('sms_channel');
                $notice->notice_content    = $request->input('push_content', '');
                $notice->notice_remark     = $request->input('push_remark', '');
                $notice->notice_mobiles    = $request->input('users');
                $notice->notice_userids    = $userIds;
                $notice->notice_count      = $request->input('user_count');
                $notice->completed_count   = 0;

                $notice->started_at        = Carbon::now();

                $notice->admin_user_id   = Constants::getAdminId();
                $notice->admin_user_name = Constants::getAdminName();
            }
            $notice->save();

            $jobData = MethodUtil::assemblyJobData($notice);
            $job = new NoticeManageJob($jobData);
            $this->dispatch($job);

            DB::connection('gaea')->commit();
        } catch(\Exception $e) {
            DB::connection('gaea')->rollback();
            throw $e;
        }

        return response()->clientSuccess(['id'=>$notice->id]);
    }

    /**
     * @brief fetchNotice 获取通知列表
     * @Param $request
     * @Return  
     */
    public function fetchNotices(Request $request)
    {
        $query = OpNotice::orderBy('created_at', 'desc');

        $paginator = new Paginator($request);
        $notices = $paginator->runQuery($query);

        return $this->responseList($paginator, $notices);
    }
    
    /**
     * @brief responseList 组装数据
     * @Param $paginator
     * @Param $collection
     * @Return  
     */
    protected function responseList($paginator, $collection, $callee='export')
    {
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }
}
