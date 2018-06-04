<?php
namespace App\Components\ClientApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

use App\Components\Utils\ErrorCodes;

class Response extends JsonResponse
{
    /**
     * Constructor.
     *
     * @param  mixed  $data
     * @param  int    $status
     * @param  array  $headers
     * @param  int    $options
     */
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        # if (config('app.debug')) {
        #     $options |= JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        # }

        parent::__construct($data, $status, $headers, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data = [])
    {
        /**
         * 将数组中的null转为空字符串 为了方便客户端解json
         * @author Yuchen Wang
         */
        if (is_array($data)) {
            if (isset($data['results']) && $data['results'] instanceof Collection) {
                $data['results'] = $data['results']->toArray();
            }
            array_walk_recursive($data, function (&$value, $key) {
                if (is_null($value)) {
                    $value = '';
                }
            });
        }

        parent::setData($data);
    }
}
