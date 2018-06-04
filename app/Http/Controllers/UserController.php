<?php
/**
 * Created by PhpStorm.
 * User: weichen
 * Date: 15/9/25
 * Time: 下午3:21
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Components\Utils\Paginator;
use App\Components\Utils\ErrorCodes;

use App\Components\Utils\CurlUtil;
use PhpSpec\Formatter\Html\Template;

use Illuminate\Support\Facades\Input;


class UserController extends Controller
{

//    protected $userCenterApi;

    function __construct(){

//        $userCenterApi = app()->make('UserCenterApiServer');

    }

    function userList(){

        $url_params = Input::All();
        if(count($url_params) > 0){
            foreach($url_params as $key => $value){
                if($value != ''){
                    $arr_search[$key] = $value;
                }
            }
        }

        //test:设置
        if(!array_key_exists('user_id',$arr_search)){
            $arr_search['user_id'] = 230;
        }

        $data = app()['UserCenterApiServer']->userList($arr_search);
        $result = json_decode($data['data'],true);

        $result['data'][1] = $result['data'][0];
        $result['data'][2] = $result['data'][0];
        $result['data'][3] = $result['data'][0];
        $result['data'][4] = $result['data'][0];

        $result['data'][1]['basic']['id'] = '235';
        $result['data'][1]['basic']['name'] = '周';
        $result['data'][2]['basic']['id'] = '236';
        $result['data'][2]['basic']['name'] = '刘';
        $result['data'][3]['basic']['id'] = '237';
        $result['data'][3]['basic']['name'] = '渠';
        $result['data'][4]['basic']['id'] = '238';
        $result['data'][4]['basic']['name'] = '翟';

        if ($result['code'] == 0) {
            $arr = app()['UserCenterApiServer']->formatData($result['data']);
            return response()->clientSuccess($arr);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }

    function userDetail(){

        $url_params = Input::All();
        if(count($url_params) > 0){
            foreach($url_params as $key => $value){
                if($value != ''){
                    $arr_search[$key] = $value;
                }
            }
        }

        //$user_id = Input::get('user_id', -1);
        //$arr_search = array('user_id' => $user_id);

        $data = app()['UserCenterApiServer']->userDetail($arr_search);
        $result = json_decode($data['data'],true);

        if ($result['code'] == 0) {
            $arr = app()['UserCenterApiServer']->formatData($result['data'])[0];
            return response()->clientSuccess($arr);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }

    }

    function userDriver(){

    }

    function userAuth(){

        $url_params = Input::All();
        if(count($url_params) > 0){
            foreach($url_params as $key => $value){
                if($value != ''){
                    $arr_search[$key] = $value;
                }
            }
        }

        //test:设置
        if(!array_key_exists('user_id',$arr_search)){
            $arr_search['user_id'] = 230;
        }

        $data = app()['UserCenterApiServer']->userList($arr_search);
        $result = json_decode($data['data'],true);

        $result['data'][1] = $result['data'][0];
        $result['data'][2] = $result['data'][0];
        $result['data'][3] = $result['data'][0];
        $result['data'][4] = $result['data'][0];

        $result['data'][1]['basic']['id'] = '235';
        $result['data'][1]['basic']['name'] = '周';
        $result['data'][2]['basic']['id'] = '236';
        $result['data'][2]['basic']['name'] = '刘';
        $result['data'][3]['basic']['id'] = '237';
        $result['data'][3]['basic']['name'] = '渠';
        $result['data'][4]['basic']['id'] = '238';
        $result['data'][4]['basic']['name'] = '翟';

        if ($result['code'] == 0) {
            $arr = app()['UserCenterApiServer']->formatData($result['data']);
            return response()->clientSuccess($arr);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }

    function userCar(){

    }

    function userMatch(){

    }

}