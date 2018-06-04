<?php
namespace App\Components\ClientApi\Middleware;

use App\Components\ClientApi\Request as ClientRequest;
use App\Components\ClientApi\Response as ClientResponse;
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\QAlarmUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;

use DB;
use Closure;
use Exception;

class Record
{
    protected $clientRequest;

    public function __construct(ClientRequest $client_request)
    {
        $this->clientRequest = $client_request;
    }

    public function handle($request, Closure $next)
    {
        // 记录请求
        DB::connection('qalarm')->enableQueryLog();
        $requestId = MethodUtil::getUniqueId();
        $timeStart = microtime(true);

        // 忽略对首页的访问请求
        if ($request->root() == $request->fullUrl()) {
            return $next($request);
        }

        $before = [
            'requestId'        => $requestId,
            'timeStart'        => $timeStart,
            'fullUrl'          => $request->fullUrl(),
            'ips'              => $request->ips(),
            'params'           => $request->all(),
        ];
        LogUtil::info('in', $before, LogUtil::LOG_RECORD);

        
        $response = $next($request);

        // 记录请求结果
        $timeCost = microtime(true) - $timeStart;

        $after = [
            'requestId'        => $requestId,
            'timeCost'         => $timeCost,
            'fullUrl'          => $request->fullUrl(),
            'output'           => json_decode($response->getContent(), true),
        ];
        LogUtil::info('out', $after, LogUtil::LOG_RECORD);


        if ($timeCost > env('REQUEST_SLOWLOG_TIMEOUT', 3)) {
            //FIXME 指定connection
            $queries = DB::getQueryLog();
            $message = sprintf('url: %s, time: %s s, sqls: %s', $request->path(), $timeCost, json_encode($queries));
            QAlarmUtil::send(QAlarmUtil::MOD_SLOW, 1, $message);
        }

        return $response;
    }
}
