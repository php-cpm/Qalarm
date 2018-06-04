<?php

namespace App\Http\Controllers;

use App\Models\Gaea\AdminMenuPermit;
use App\Models\Gaea\AdminRolePermit;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Exception;
use View;
use Log;
use DB;

use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\Paginator;
use App\Components\Utils\DomainUserUtil;

use App\Models\Gaea\AdminRole;
use App\Models\Gaea\AdminAuth;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\AdminTicket;
use App\Models\Gaea\AdminUserRole;
use App\Models\Gaea\WorkflowParticipator;

use Qiniu\Auth as QiniuAuth;
use Qiniu\Storage\UploadManager as QiniuUploadManager;

class AdminController extends Controller
{
    public function index()
    {/*{{{*/
        return response()->clientSuccess([
            'pong' => microtime(true),
            ]);
    }/*}}}*/

    /**
     * @brief login 处理登陆操作
     *
     * @Return  
     */
    public function login(Request $request)
    {/*{{{*/
        // $this->validate($request, [
        //     'username'  => 'required',
        //     'password'  => 'required',
        // ]);

        $username = $request->input('username');
        $password = md5($request->input('password'));
        $user = AdminUser::where('username', $username)
                         ->where('password', $password)
                         ->first();
        if (is_null($user)) {
            return response()->clientError(-1, 'error');
        } else {
            // 生成ticket信息，入库
            $token = md5($username).md5(microtime(true));
            $ticket = new AdminTicket;
            {
                $ticket->admin_user_id = $user->id;
                $ticket->ticket = $token;
                $ticket->timestamp = date('Y-m-d H:i:s', time());
            }
            $ticket->save();

            //登陆之后获取权限
            $permitJson = json_encode($this->getUserPermit($user->id));
            $result = [
                'user'=>[
                    'admin_user_id'         => $user->id, 
                    'admin_user_name'       => $user->nickname,
                    'ticket'                => $token, 
                    'permits'               => $permitJson
                ]
            ]; 
            return response()->clientSuccess($result);
        }
    }/*}}}*/

    public function logout(Request $request)
    {/*{{{*/
        $domain = DomainUserUtil::getInstance();
        $response = new \Illuminate\Http\Response(['ref' => $domain->_logout_url]); 
        return $response;
    }/*}}}*/

    public function nav(Request $request)
    {/*{{{*/
        $adminId = Constants::getAdminId();
        // 获取用户的所有权限
//        $collection = DB::connection('gaea')->select("select distinct(t3.id), t3.* from admin_user t1, admin_role t2, admin_auth t3 where  LOCATE(concat(',',t2.id,','),concat(',',t1.`roleid_set`,','))!=0 and LOCATE(concat(',',t3.id,','),concat(',',t2.`authid_set`,','))!= 0  and t1.id=?", [$adminId]);
        $str = <<<EOD
        SELECT * FROM (
            (SELECT distinct(d.id) as idd, d.*,c.public_permit
            FROM admin_user_role as a
            JOIN admin_role_permit as b on a.role_id = b.role_id
            JOIN admin_menu_permit as c on b.permit_id = c.id
            JOIN admin_auth as d ON c.menu_id = d.id
            where user_id = ? order by d.sid)
            UNION
            (SELECT distinct(b.id) as idd, b.*,a.public_permit from admin_menu_permit as a
            JOIN admin_auth as b on a.menu_id = b.id
            where public_permit = 1)
        ) tt order by sid,mid;
EOD;
        $collection = DB::connection('gaea')->select("$str", [$adminId]);

        $nav = "<ul class=\"nav\">";
        // mid倒序排序，sid正序排列，sid为0的是主导航
        $group = array();
        foreach ($collection as $auth) {
            $group[$auth->mid][$auth->sid] = $auth;
        }

        ksort($group);

        foreach ($group as $mid => $childAuths) {
            foreach ($childAuths as $sid => $auth) {
                // 根目录
                if ($sid == 0) {
                    $nav .= $this->ALevelNav($auth->auth_name, $auth->auth_url, $auth->icon_class, $auth->badge_name, $auth->badge_class);
                } else {
                    $nav .= $this->BLevelNav($auth->auth_name, $auth->auth_url, $auth->icon_class, $auth->badge_name, $auth->badge_class);
                }
            }
            $nav .= "</ul>";
            $nav .= "<li class=\"line \"></li>";
        }
        $nav .= "</ul>";

        $html =   $nav;


        $user_data = array();
//        $username = $adminId;
//        $username = $request->input('username');
//        $password = md5($request->input('password'));
        $user = AdminUser::where('id', $adminId)->first();
        if (is_null($user)) {
            return response()->clientError(-1, 'error');
        } else {
            // 生成ticket信息，入库
            $token = md5($user->username).md5(microtime(true));
            $ticket = new AdminTicket;
            $ticket->admin_user_id = $user->id;
            $ticket->ticket = $token;
            $ticket->timestamp = date('Y-m-d H:i:s', time());
            $ticket->save();

            //登陆之后获取权限
            $permitJson = json_encode($this->getUserPermit($user->id));

            $user_data = array(
                'nav'=>$html,
                'user'=>[
                    'admin_id'     =>$user->id,
                    'admin_name'   =>$user->nickname,
                    'ticket'            =>$token,
                    'permits'           =>$permitJson,
                ]
            );

            if (!empty($user->head_img)) {
                $user_data['user']['head_img'] = Constants::QINIU_HOST.'/'.$user->head_img;

            }
            return response()->clientSuccess($user_data);
        }
    }/*}}}*/

    private function ALevelNav($authName, $url, $icon, $badgeName, $badgeClass) 
    {/*{{{*/
        $nav = "<li ng-class=\"{active:\$state.includes('$url')}\">";
        if (!empty($url)) {
            $nav .= "<a ui-sref=\"$url\" class=\"auto\">";
        } else {
            $nav .= "<a class=\"auto\">";
        }
        $nav .= "<span class=\"pull-right text-muted\">";
        if ($authName != '我的业务') {
            $nav .= "<i class=\"fa fa-fw fa-angle-right text\"></i>";
            $nav .= "<i class=\"fa fa-fw fa-angle-down text-active\"></i>";
        }
        $nav .= "</span>";
        if (!empty($badgeName)) {
            $nav .= "<b class=\"$badgeClass\">$badgeName</b>";
        }
        $nav .= " <i class=\"$icon\"></i>";
        $nav .= "<span>$authName</span>";
        $nav .= "</a>";
        $nav .= " <ul class=\"nav dk\">";
        // $nav .= " <ul class=\"nav nav-sub dk\">";

        return $nav;
    }/*}}}*/

    private function BLevelNav($authName, $url, $icon, $badgeName, $badgeClass)
    {/*{{{*/
        $nav = "<li ui-sref-active=\"active\">";
        if (empty($url)) $url = 'app.myjob';
        $nav .= "<a ui-sref=\"$url\">";
        if (!empty($badgeName)) {
            $nav .= "<b class=\"$badgeClass\">$badgeName</b>";
        }
        $nav .= "<span>$authName</span>";
        $nav .= "</a>";
        $nav .= "</li>";

        return $nav;
    }/*}}}*/
    
    /**
     * @brief adminRole adminrole CRUD
     * @Param $request
     * @Return  
     */
    public function adminRole(Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
         ]);

        $action = $request->input('action');
        
        switch ($action) {
        case Constants::REQUEST_TYPE_ADD:
            $this->validate($request, [
                'role_name' => 'required',
                'mid' => 'required',
                'sid' => 'required',
            ]);

            $roleName = $request->input('role_name');
            $adminRole = AdminRole::where('role_name', $roleName)->first();
            if ($adminRole != null) {
                return response()->clientError(-1, 'existed');
            }

            DB::connection('gaea')->beginTransaction();
            try {

                $role = new AdminRole();
                $role->role_name = $request->input('role_name');
                $role->mid = $request->input('mid');
                $role->sid = $request->input('sid');
                $role->save();

                $permits = $request->input('role_permits');
                if(is_array($permits)){
//                    $rolePermits = explode(",", $permits);
                    foreach($permits as $item){
                        $adminRolePermit = new AdminRolePermit();
                        $adminRolePermit->role_id = $role->id;
                        $adminRolePermit->permit_id = $item;
                        $adminRolePermit->save();
                    }
                }
                DB::connection('gaea')->commit();
            } catch(\Exception $e) {
                DB::connection('gaea')->rollback();
                throw $e;
            }
            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_GET:
            $collection = AdminRole::where([])->select(['id','role_name'])
                ->orderBy('id', 'desc')->get();
            return response()->clientSuccess($collection);
            break;
        case Constants::REQUEST_TYPE_DELETE:
            $this->validate($request, [
                'id' => 'required',
            ]);
            return response()->clientSuccess([AdminRole::where('id', $request->input('id'))->delete()]);
            break;
        case Constants::REQUEST_TYPE_UPDATE:
            $this->validate($request, [
                'id'        => 'required',
                'role_name' => 'required',
                'mid'       => 'required',
                'sid'       => 'required',
            ]);

            $roleName = $request->input('role_name');
            $adminRole = AdminRole::where('role_name', $roleName)
                ->where('id','<>', $request->input('id'))->first();
            if ($adminRole != null) {
                return response()->clientError(-1, 'existed');
            }

            DB::connection('gaea')->beginTransaction();
            try {
                $id = $request->input('id');
                $role = AdminRole::where('id', $id)->first();
                $role->role_name = $request->input('role_name');
                $role->mid = $request->input('mid');
                $role->sid = $request->input('sid');
                $role->save();

                $permits = $request->input('role_permits');
                if(is_array($permits)){
                    AdminRolePermit::where('role_id', '=', $role->id)->delete();
                    //$rolePermits = explode(",", $permits);
                    foreach($permits as $item){
                        $adminRolePermit = new AdminRolePermit();
                        $adminRolePermit->role_id = $role->id;
                        $adminRolePermit->permit_id = $item;
                        $adminRolePermit->save();
                    }
                }

                DB::connection('gaea')->commit();
            } catch(\Exception $e) {
                DB::connection('gaea')->rollback();
                throw $e;
            }

            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_LIST:
            $paginator = new Paginator($request); 
            $collection = AdminRole::orderBy('id', 'desc')->get();
            return $this->responseList($paginator, $collection);
            break;
        }
    }/*}}}*/
    
    /**
     * @brief adminUser 用户管理
     * @Param $request
     * @Return  
     */
    public function adminUser(Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
         ]);

        $action = $request->input('action');

        switch ($action) {
        case Constants::REQUEST_TYPE_ADD:
            $this->validate($request, [
                'username'  => "required",
                'nickname'  => "required",
                'cityid'    => "required",
                'cityids'   => "required"
            ]);

            $userName = $request->input('username');
            $adminUser = AdminUser::where('username', $userName)->first();
            if ($adminUser != null) {
                return response()->clientError(-1, 'existed');
            }

            DB::connection('gaea')->beginTransaction();
            try {

                $user = new AdminUser;
                $user->username = $request->input('username');
                $user->nickname = $request->input('nickname');
                $user->cityid   = $request->input('cityid');
                $user->cityids  = join(',', $request->input('cityids'));
                $user->mobile   = $request->input('mobile', '000'); //默认值
                //先sha1加密，再md5加密, 网络上传输的为sha1加密后的密文
                $user->password = md5(sha1($request->input('password', '999999')));
                $user->save();

                $roles = $request->input('roles');
                if(is_array($roles)){
                    // AdminUserRole::where('user_id', '=', $user->id)->delete();
                    $roles = $request->input('roles');
                    foreach($roles as $item){
                        $userRole = new AdminUserRole();
                        $userRole->user_id = $user->id;
                        $userRole->role_id = $item;
                        $userRole->save();
                    }
                }

                DB::connection('gaea')->commit();
            } catch(\Exception $e) {
                DB::connection('gaea')->rollback();
                throw $e;
            }
            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_GET:
            $this->validate($request, [
                'id'  => 'required'
            ]);
            $adminUser = AdminUser::where('id','=', $request->input('id'))->first();
            if ($adminUser == null) {
                return response()->clientError(-1, 'not find data');
            }
            if(!empty($adminUser->head_img)) {
                // $adminUser->head_img = Constants::QINIU_HOST.'/'.$adminUser->head_img;
                $adminUser->head_img = '';
            }
            return response()->clientSuccess(["result" => $adminUser]);
            break;
        case Constants::REQUEST_TYPE_DELETE:
            $this->validate($request, [
                'id'  => 'required'
            ]);
            $id = $request->input('id');

            $adminUser = AdminUser::find($id);
            return response()->clientSuccess([$adminUser->delete()]);
            break;
        case Constants::REQUEST_TYPE_UPDATE:

            if (array_key_exists('user_head',$request->input())){
                //修改头像
                $admin_user_id  = Constants::getAdminId();
                $adminUser = AdminUser::where('id','=', $admin_user_id)->first();
                if ($adminUser == null) {
                    return response()->clientError(-1, 'not find data');
                }else{
                    $uerHead = $request->input('user_head');
                    $uerHead = str_replace('data:image/png;base64,', '', $uerHead);
                    $uerHead = str_replace(' ', '+', $uerHead);
                    $data = base64_decode($uerHead);

                    if (!is_dir( Constants::USER_HEAD_IMG_PATH )) {
                        mkdir ( Constants::USER_HEAD_IMG_PATH, '666' );
                    }
                    $headImgName = 'head_'.$adminUser->username.'.png';
                    $fullHeadImgName = Constants::USER_HEAD_IMG_PATH.$headImgName;
                    //临时保存;最终上传到七牛
                    file_put_contents($fullHeadImgName, $data); //临时保存
                    $qiniuAuth = new QiniuAuth(Constants::QINIU_ACCESS_KEY,Constants::QINIU_SECRET_KEY);
                    $qiniuToken = $qiniuAuth->uploadToken(Constants::QINIU_BUCKET);
                    // 上传到七牛后保存的文件名
                    $qiniuImgName = 'head_'.time().$adminUser->username.'.png';
                    $uploadMgr = new QiniuUploadManager();
                    list($ret, $err) = $uploadMgr->putFile($qiniuToken, $qiniuImgName, $fullHeadImgName);
                    if ($err !== null) {
                        return response()->clientError(-113, '更新头像失败');
                    } else {
                        if(unlink($fullHeadImgName)){
                            $adminUser->head_img = $qiniuImgName;
                            $adminUser->save();
                            return response()->clientSuccess(["head_img" => Constants::QINIU_HOST.'/'. $qiniuImgName]);
                        }
                        return response()->clientError(-113, '更新头像失败');
                    }
                }
            }

            $this->validate($request, [
                'username'  => "required",
                'nickname'  => "required",
                'cityid'    => "required",
                'cityids'   => "required"
            ]);
            $userName = $request->input('username');
            $adminUser = AdminUser::where('username','=', $userName)
                ->where('id','<>', $request->input('id'))->first();

            if ($adminUser != null) {
                return response()->clientError(-1, 'existed');
            }

            $adminUser = AdminUser::where('id','=', $request->input('id'))->first();
            if ($adminUser == null) {
                return response()->clientError(-1, 'not find data');
            }

            DB::connection('gaea')->beginTransaction();
            try {

                $adminUser->username = $request->input('username');
                $adminUser->nickname = $request->input('nickname');
                $adminUser->mobile = $request->input('mobile');
                $adminUser->cityid = $request->input('cityid');
                $adminUser->cityids = join(',', $request->input('cityids'));
                $adminUser->save();

                $roles = $request->input('roles');
                if(is_array($roles)){
                    AdminUserRole::where('user_id', '=', $adminUser->id)->delete();
                    $roles = $request->input('roles');
                    foreach($roles as $item){
                        $userRole = new AdminUserRole();
                        $userRole->user_id = $adminUser->id;
                        $userRole->role_id = $item;
                        $userRole->save();
                    }
                }

                DB::connection('gaea')->commit();
            } catch(\Exception $e) {
                DB::connection('gaea')->rollback();
                throw $e;
            }
            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_LIST:
            $paginator = new Paginator($request); 
            $collection = AdminUser::orderBy('id', 'desc')
                                    ->get();
            return $this->responseList($paginator, $collection);
            break;
        }
    }/*}}}*/
    
    /**
     * @brief adminAuth
     * @Param $request
     * @Return  
     */
     public function adminAuth(Request $request)
     {/*{{{*/
         Log::info([$request->header('ticket')]);

        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
         ]);

        $action = $request->input('action');
        
        switch ($action) {
        case Constants::REQUEST_TYPE_ADD:
            $this->validate($request, [
                'mid'       => 'required',
                'sid'       => 'required',
                'auth_name' => 'required',
            ]);
            $authName = $request->input('auth_name');
            $auth = AdminAuth::where('auth_name', $authName)->first();
            if ($auth != null) {
                return response()->clientError(-1, 'existed');
            }

            $auth = new AdminAuth(); 
            {
                $auth->mid          = $request->input('mid');
                $auth->sid          = $request->input('sid');
                $auth->auth_name    = $request->input('auth_name');
                $auth->auth_url     = $request->input('auth_url');
                $auth->icon_class   = $request->input('icon_class');
            }
            return response()->clientSuccess([$auth->save()]);
            break;
        case Constants::REQUEST_TYPE_GET:
            break;
        case Constants::REQUEST_TYPE_DELETE:
            $this->validate($request, [
                'id'  => 'required'
            ]);
            $id = $request->input('id');

            $auth = AdminAuth::find($id);
            return response()->clientSuccess([$auth->delete()]);
            break;
        case Constants::REQUEST_TYPE_UPDATE:
            $this->validate($request, [
                'id'            => 'required',
                'mid'           => 'required',
                'sid'           => 'required',
                'auth_name'     => 'required',
            ]);
            $authName = $request->input('auth_name');
            $auth = AdminAuth::where('auth_name', $authName)
                ->where('id','<>', $request->input('id'))->first();
            if ($auth != null) {
                return response()->clientError(-1, 'existed');
            }

            $auth = AdminAuth::where('id','=', $request->input('id'))->first();
            if ($auth == null) {
                return response()->clientError(-1, 'not find data');
            }

            $auth->mid          = $request->input('mid');
            $auth->sid          = $request->input('sid');
            $auth->auth_name    = $request->input('auth_name');
            $auth->auth_url     = $request->input('auth_url');
            $auth->icon_class   = $request->input('icon_class');
            return response()->clientSuccess([$auth->save()]);
            break;
        case Constants::REQUEST_TYPE_LIST:
            $paginator = new Paginator($request); 
            $query = AdminAuth::orderBy('id', 'asc');
            $collection = $paginator->runQuery($query);
            return $this->responseList($paginator, $collection);
            break;
        }
    }/*}}}*/

    public function adminMenuPermit(Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');

        switch ($action) {
            case Constants::REQUEST_TYPE_ADD:
                $this->validate($request, [
                    'menu_id'           => "required",
                    'sub_page_code'     => "required",
                    'sub_page_name'     => "required",
                    'permit_code'       => "required",
                    'permit_name'       => "required",
                    'public_permit'     => "required"
                ]);

                $menuPermit = new AdminMenuPermit();
                {
                    $menuPermit->menu_id            = $request->input('menu_id');
                    $menuPermit->sub_page_code      = $request->input('sub_page_code');
                    $menuPermit->sub_page_name      = $request->input('sub_page_name');
                    $menuPermit->permit_code        = $request->input('permit_code');
                    $menuPermit->permit_name        = $request->input('permit_name');
                    $menuPermit->public_permit      = $request->input('public_permit');
                }
                return response()->clientSuccess([$menuPermit->save()]);
                break;
            case Constants::REQUEST_TYPE_GET:
                $collection = AdminMenuPermit::where([])->select(['id','menu_id','sub_page_code','sub_page_name','permit_code','permit_name'])
                    ->orderBy('id', 'desc')->get();
                return response()->clientSuccess($collection);
                break;
            case Constants::REQUEST_TYPE_DELETE:
                $this->validate($request, [
                    'id'  => 'required'
                ]);
                $id = $request->input('id');

                $menuPermit = AdminMenuPermit::find($id);
                return response()->clientSuccess([$menuPermit->delete()]);
                break;
            case Constants::REQUEST_TYPE_UPDATE:
                $this->validate($request, [
                    'id'                => "required",
                    'menu_id'           => "required",
                    'sub_page_code'     => "required",
                    'sub_page_name'     => "required",
                    'permit_code'       => "required",
                    'permit_name'       => "required",
                    'public_permit'     => "required",
                ]);

                $menuPermit = AdminMenuPermit::where('id','=', $request->input('id'))->first();
                if ($menuPermit == null) {
                    return response()->clientError(-1, 'not find data');
                }

                $menuPermit->menu_id = $request->input('menu_id');
                $menuPermit->sub_page_code = $request->input('sub_page_code');
                $menuPermit->sub_page_name   = $request->input('sub_page_name');
                $menuPermit->permit_code  = $request->input('permit_code');
                $menuPermit->permit_name   = $request->input('permit_name');
                $menuPermit->public_permit   = $request->input('public_permit');
                return response()->clientSuccess([$menuPermit->save()]);
                break;
            case Constants::REQUEST_TYPE_LIST:
                $paginator = new Paginator($request);
                $collection = AdminMenuPermit::orderBy('id', 'desc')
                    ->get();
                return $this->responseList($paginator, $collection);
                break;
        }
    }/*}}}*/

    public function fetchWorkflowParticipators(Request $request)
    {/*{{{*/
        $query = WorkflowParticipator::orderBy('created_at', 'desc');

        $paginator = new Paginator($request);
        $all = $paginator->runQuery($query);

        return $this->responseList($paginator, $all);
    }/*}}}*/

    public function updateWorkflowParticipator(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'id'          => 'required',
            'participator'=> 'required',
        ]);

        $participator = WorkflowParticipator::where('id', $request->input('id'))->first();
        if (is_null($participator)) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '不存在此项目');
        }

        $participator->participator = $request->input('participator');
        $participator->save();

        return response()->clientSuccess([]);
    }/*}}}*/

    // example
    // public function adminUser(Request $request)
    // {
    //     $action = $request->input('action');

    //     $actions = MethodUtil::getActions();
    //     $this->validate($request, [
    //         'action'  => "required|in:$actions"
    //      ]);

    //     switch ($action) {
    //     case Constants::REQUEST_TYPE_ADD:
    //         break;
    //     case Constants::REQUEST_TYPE_GET:
    //         break;
    //     case Constants::REQUEST_TYPE_DELETE:
    //         break;
    //     case Constants::REQUEST_TYPE_UPDATE:
    //         break;
    //     case Constants::REQUEST_TYPE_LIST:
    //         break;
    //     }
    // }

     // 构造订单列表的响应数据
    protected function responseList($paginator, $collection, $callee = 'export')
    {/*{{{*/
        return response()->clientSuccess([
            'page' => $paginator->info($collection),
            'results' => $collection->map(function ($item, $key) use ($callee) {
                 return call_user_func([$item, $callee]);
             }),
        ]);
    }/*}}}*/

    function getUserPermit($adminId =0)
    {/*{{{*/
        $str = <<<EOD
                (SELECT c.menu_id,d.auth_url,d.auth_name,c.sub_page_code,c.sub_page_name,c.permit_code,c.permit_name
                FROM admin_user_role as a
                JOIN admin_role_permit as b on a.role_id = b.role_id
                JOIN admin_menu_permit as c on b.permit_id = c.id
                JOIN admin_auth as d ON c.menu_id = d.id
                where user_id = ?)
                union
                (SELECT c.menu_id,d.auth_url,d.auth_name,c.sub_page_code,c.sub_page_name,c.permit_code,c.permit_name
                FROM admin_menu_permit as c
                JOIN admin_auth as d ON c.menu_id = d.id
                where public_permit = 1)
EOD;
        $collection = DB::connection('gaea')->select("$str", [$adminId]);

        //取出人员所有权限到数组
        $permits = array();
        foreach ($collection as $item) {
            $permits[$item->auth_url][$item->sub_page_code][] = $item->permit_code ;
        }

        //权限数组准备转json数组
        $result = array();
        foreach ($permits as $key=>$val) {

            $resultItem = array();
            $resultItem["route"] = $key;

            //循环子页面tab
            foreach ($val as $key2=>$val2) {

                //循环权限
                $tabPermits = array();
                foreach ($val2 as $key3=>$val3) {
                    $tabPermits[$val3] = 1;
                }

                $resultItem["data"][] = array(
                    "subpage"=>$key2,
                    "permit"=>$tabPermits
                );
            }
            $result[] = $resultItem;
        }
        return $result;
    }/*}}}*/
}
