<?php
namespace App\Exceptions;

use App\Components\ClientApi\Response as ApiResponse;
use App\Exceptions\ApiException;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Components\Utils\QAlarmUtil;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
    ];

    /**
     * Determine if the given exception is an API exception.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function isApiException(Exception $e)
    {
        return $e instanceof ApiException;
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        $errors = [];

        $errors[] = sprintf(
            'Exception \'%s\' with message \'%s\' in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        $errors[] = $e->getTraceAsString();

        $errmsg = implode("\n", $errors); 

        // 记录错误日志
        $this->log->error($errmsg);

        // 报错到qalarm
        QAlarmUtil::send(QAlarmUtil::MOD_EXCEPTION, 1, $errmsg);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        // Build reponse data
        if ($this->isHttpException($e)) {
            $status = $code = $e->getStatusCode();
            $msg = $e->getMessage();
            if (!$msg && isset(BaseResponse::$statusTexts[$status])) {
                $msg = BaseResponse::$statusTexts[$status];
            }
        } elseif ($this->isApiException($e)) {
            $status = 200;
            $code = $e->getCode();
            $msg = $e->getMessage();
        } else {
            $status = 500;
            $code = $e->getCode();
            if ($code == 0) {
                $code = 500;
            }
            $msg = '系统错误'; // FIXME
        }
        $data = ['errno'  => $code, 'errmsg'   => $msg, 'data' => []];

        // Merge debug data
        if (config('app.debug') && !$this->isHttpException($e)) {
            $data['curl'] = $this->buildCurl($request);
            $data['exception'] = $this->buildException($e);
        }

        // Build client response
        $response = new ApiResponse($data, $status);

        return $response;
    }

    // 生成异常的响应数据
    protected function buildException($exception)
    {
        return [
            'class'     => get_class($exception),
            'message'   => $exception->getMessage(),
            'code'      => $exception->getCode(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => $exception->getTrace(),
        ];
    }

    // 生成curl命令
    protected function buildCurl($request)
    {
        // 生成curl命令
        $args = [];
        $args[] = escapeshellarg($request->getUri());
        foreach ($request->header() as $name => $lines) {
            if (!in_array(strtolower($name), ['clientinfo', 'clientauth'])) {
                continue;
            }
            foreach ($lines as $line) {
                $args[] = '-H '.escapeshellarg($name.': '.$line);
            }
        }
        if ($request->isMethod('post')) {
            $args[] = '-d '.escapeshellarg($request->getContent());
        }
        $cmd = 'curl '.implode(' ', $args);

        // 将curl命令记录到日志中，便于重现问题
        Log::debug($cmd);

        return $cmd;
    }
}
