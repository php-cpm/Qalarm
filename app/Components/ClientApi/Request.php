<?php
namespace App\Components\ClientApi;

use App\Components\MagicKey;
use App\Models\Gaea\UserTicket;
use Exception;
use Illuminate\Http\Request as HttpRequest;
use Log;

use App\Models\Gaea\AdminTicket;
use App\Models\Gaea\AdminUser;
use App\Components\Utils\Constants;
use App\Components\Utils\DomainUserUtil;

/*
 * 对客户端请求中额外信息的封装
 * https://worktile.com/project/cf8a4472b7ac80/page/3536d4cbe338e4
 */
class Request
{
    const accessToken= 'ab37655347122540857b44950fca0ba3';

    protected $info;

    protected $auth;

    protected $ticket;

    protected $user;

    protected $userLoaded = false;

    /*
     * 认证签名
     */
    public function setAuthByRequest(HttpRequest $request)
    {

        //$userInfo = DomainUserUtil::getInstance()->checkQUCookie($request->fullUrl());

        //// FIXME 基于ticket的认证体系, 客户端会在header里带TICKET字段,根据此字段来认证
        //$ticket = $request->header('TICKET');

        //// 检查签名
        //$adminTicket = AdminTicket::where('ticket', $ticket)->first();
        //if (is_null($adminTicket)) {
        //    throw new Exception('sign verify failed');
        //}

        //// session失效
        //if ((time() - strtotime($adminTicket->timestamp)) > Constants::SESSION_TIMEOUT) {
        //    throw new Exception('sign verify failed');
        //}

        //$admin = AdminUser::where('id', $adminTicket->admin_user_id)->first();
        //Constants::$admin = array('admin_id'=>$admin->id, 'admin_name'=>$admin->nickname);
    }

    /*
     * 解析Header中的clientInfo, clientAuth
     */
    protected function parseItem(HttpRequest $request, $key)
    {
        $raw = $request->input($key);
        if (is_null($raw)) {
            throw new Exception($key.' not found');
        }
        
        return $raw;
    }

}
