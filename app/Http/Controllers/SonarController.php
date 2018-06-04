<?php

namespace App\Http\Controllers;

use Illuminate\Log\Writer as Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
//use JenkinsKhan\Jenkins;
use DB;
use Log;
use App\Components\Utils\LogUtil;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;

use App\Components\Sonar\Sonar;
use App\Components\Jenkins\JenkinsDslHelper;
use App\Components\Jenkins\JenkinsHelp;

class SonarController extends Controller
{
    public function fetchMetrics(Request $request, Logger $logger)
    {/*{{{*/

        dd(LogUtil::getLoggerInstancesFiles());
        dd(($logger->getMonolog()->getHandlers()));
        $projectName = 'gaea1';
        $repo = 'git@gitlab.corp.ttyongche.com:core/gaea.git';
        $branch = 'master';

        $job = app('sonar')->createAndUpdateJobs($projectName, $repo, $branch);

        dd($job);

        $run = app('sonar')->launchJobs($projectName, $branch, 'hahah323', 'app');

        dd($run);

        $ret = app('sonar')->projectMetrics('gaea');
        return response()->clientSuccess($ret);
    }/*}}}*/
    
}
