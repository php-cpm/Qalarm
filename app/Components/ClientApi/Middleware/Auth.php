<?php
namespace App\Components\ClientApi\Middleware;

use App\Components\ClientApi\Request as ClientRequest;
use App\Components\ClientApi\Response as ClientResponse;
use App\Components\Utils\ErrorCodes;
use Closure;
use Cookie;
use Exception;
use App\Components\Utils\DomainUserUtil;
use App\Components\Utils\Constants;
use App\Models\Gaea\AdminUser; 

class Auth
{
    protected $clientRequest;

    public function __construct(ClientRequest $client_request)
    {
        $this->clientRequest = $client_request;
    }

    public function handle($request, Closure $next)
    {
        // Process clientAuth
        try {

            // $userInfo = DomainUserUtil::getInstance()->checkUCookie('http://'.$request->server('HTTP_HOST') .'/' );           
            $admin = AdminUser::where('username', 'admin')->first();

            // 新用户
            if ($admin == null) {
                $admin = new AdminUser;
                {
                    $admin->username = $userInfo['userName'];
                    $admin->nickname = $userInfo['display'];
                    $admin->mail     = $userInfo['userMail'];
                    $admin->cityid   = 131;
                    $admin->cityids  = 131;
                }
                $admin->save();
            }

            // 设置系统用户信息
            Constants::setAdmin(['admin_id'=>$admin->id, 'admin_name'=>$admin->nickname, 'admin_account'=>$admin->username, 'admin_mail'=>$admin->mail, 'admin_mobile'=>$admin->mobile]);
        } catch (Exception $e) {
            return $this->attachHeaders($request, response()->clientError(
                ErrorCodes::ERR_AUTH_FAILED,
                $e->getMessage()
            ));
        }

        $response = $next($request);

        return $this->attachHeaders($request, $response);
    }

    /*
     * 为返回头增加Api Header
     */
    protected function attachHeaders($request, $response)
    {
        // $response->header('Api', $request->path());

        if (config('app.debug')) {
            $response->header('Api-Environment', app()->environment());
        }
        $response->header('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
