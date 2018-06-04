<?php
namespace App\Components\Jenkins;

use JenkinsKhan\Jenkins;
use App\Components\Utils\HttpUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\Utils\Constants;
use App\Components\Utils\XmlArrayHelp;
use App\Components\Jenkins\CiCdConstants;

use App\Models\Gaea\CiBuildProjectLog;
use Carbon\Carbon;

class JenkinsHelp 
{
    public static function getJenkins()
        {/*{{{*/
            $jenkins = new \JenkinsKhan\Jenkins(env('CI_JENKINS_URL'));
            if ($jenkins->isAvailable()) {
                return $jenkins;
            }

            // error
            LogUtil::warning('Jenkins not available', []);
            return $jenkins;
        }/*}}}*/

    //设置jenkins config.xml 节点参数
    public static function setJenConfigGiturl($configArr, $projectGitUrl)
    {/*{{{*/
        $configArr['project']['scm']['userRemoteConfigs']['hudson.plugins.git.UserRemoteConfig']['url'] = array('#text'=>$projectGitUrl);
        return $configArr;
    }/*}}}*/

    //设置jenkins config.xml 节点参数
    public static function setJobConfig($configArr, $config = "")
    {/*{{{*/
        //project builders javaposse.jobdsl.plugin.ExecuteDslScripts scriptText
        $configArr['project']['builders']['javaposse.jobdsl.plugin.ExecuteDslScripts']['scriptText'] = array('#text'=>$config);
        return $configArr;
    }/*}}}*/

    public static function setJenConfigDesc($configArr, $projectDesc)
    {/*{{{*/
        $configArr['project']['description'] = array('#text'=>$projectDesc);
        return $configArr;
    }/*}}}*/

    public static function getDefaultJenkinsConfig()
    {/*{{{*/
        $jobConfig =file_get_contents(app_path("Components/Jenkins/jenkins_template/dsl_job.xml"));
        
        $jobConfigArray = self::configXml2Array($jobConfig);
        $xmlFileName = 'jobCallbackForDslJob.sh';
        $callbackCmd = JenkinsDslHelper::dslCallbackCmd($xmlFileName);
        $jobConfigArray['project']['builders']['hudson.tasks.Shell']['command'] = array('#text'=>$callbackCmd);

        return $jobConfigArray;
    }/*}}}*/

    public static function getConfigArray2String($arr)
    {/*{{{*/
        return self::configArray2String($arr);
    }/*}}}*/

    public static function configXml2Array($data)
    {/*{{{*/
       return XmlArrayHelp::import($data); 
    }/*}}}*/

    public static function configArray2String($arr)
    {/*{{{*/
       return XmlArrayHelp::export($arr); 
    }/*}}}*/
    
    //根据basejob，创建逻辑Job
    public static function gaeaCreateJenkinsJob2 ($createJobName, $config = '') 
    {/*{{{*/
        //参数必须传递的
        $createJobName = $createJobName;
        $jobConfig = $config;
        //创建基础job；存在不会再次创建
        if (!static::jobIsExits(CiCdConstants::BASE_JENKINS_DSL_JOB)) {
            static::createBaseJob(CiCdConstants::BASE_JENKINS_DSL_JOB);
            LogUtil::info('jenkins basejob is not exit', ['create jenkins base job'], LogUtil::LOG_CI);
        } 

        //判断有没有创建jenkins job
        $isExistJob = static::jobIsExits($createJobName);
        /*LogUtil::info('检查job 是否存在',['job_name' => $createJobName, 'isExitsJob']);*/
        if ($isExistJob) {
            LogUtil::info('error job exits', ["jenkins job $createJobName is exit"], LogUtil::LOG_CI);
            return false;
        } else {
            $baseJobName = CiCdConstants::BASE_JENKINS_DSL_JOB;
            $configArray = JenkinsHelp::getDefaultJenkinsConfig();
            $configArray = JenkinsHelp::setJobConfig($configArray, $config);
            $jobConfig   = JenkinsHelp::getConfigArray2String($configArray);
            JenkinsHelp::getJenkins()->setJobConfig($baseJobName,$jobConfig);

            $result = JenkinsHelp::launchJenkinsJobs($baseJobName, [], '', Constants::CALL_SYNC); 
            if ($result) {
                LogUtil::info('launch  base_job success', ["jenkins job $createJobName"], LogUtil::LOG_CI);
            } else {
                LogUtil::info('launch  base_job error', ["jenkins job $createJobName"], LogUtil::LOG_CI);
            }
            return $result;
        }
    }/*}}}*/
    
    //创建basejob 然后根据basejob创建逻辑job
    public static function createBaseJob ($createJobName = '') 
    {/*{{{*/
        $configArray = JenkinsHelp::getDefaultJenkinsConfig();
        $jobConfig   = JenkinsHelp::getConfigArray2String($configArray);
        JenkinsHelp::getJenkins()->createJob($createJobName,$jobConfig);
    }/*}}}*/
    
    //检测job 是否存在于jenkins 中
    public static function jobIsExits ($createJobName = '') 
    {/*{{{*/
        $jobs = JenkinsHelp::getJenkins()->getAllJobs();
        $isExistJob = false;
        foreach ($jobs as $job) {
            if ($job['name'] == $createJobName) {
                $isExistJob = true;
                break;
            }
        }
        return $isExistJob;
    }/*}}}*/

    /**
     * @brief launchJenkinsJobs jenkins job执行函数
     * @param $jobName
     * @param $callStyle
     * @return 
     */
    public static function launchJenkinsJobs($jobName, $params = [], $gaeaBuildId = '', $callStyle = Constants::CALL_ASYNC)
    {/*{{{*/
        // 如果gaeaBuildId为空，则生成一个
        if (empty($gaeaBuildId)) {
            $gaeaBuildId = MethodUtil::getUniqueId();
        }
        $extData = json_encode(['gaea_build_id' => $gaeaBuildId]);
        //$post = array_merge(['ext' => $extData], $params);
        //$post = array_merge([CiCdConstants::BUILD_PARAMS_EXT => $extData], $params);
        $post = array_merge([CiCdConstants::BUILD_PARAMS_GAEA_BUILD_ID => $gaeaBuildId], $params);
        LogUtil::info('launch  base_job params', $post, LogUtil::LOG_CI);

        try {
            $result = static::getJenkins()->launchJob($jobName, $post);

            $timeout = 5;   // 5秒超时
            // 同步执行,先创建一个project 任务，然后等待任务完成
            if ($callStyle == Constants::CALL_SYNC) {
                $ciBuildProjectLog  = new CiBuildProjectLog();
            {
                $ciBuildProjectLog->gaea_build_id    = $gaeaBuildId; 
                $ciBuildProjectLog->jenkins_job_name = $jobName;
                $ciBuildProjectLog->weight           = 0;
                //$ciBuildProjectLog->status           = self::JENKINS_JOB_STATUS_WAITING;
                $ciBuildProjectLog->status           = CiCdConstants::JENKINS_JOB_STATUS_WAITING;
                //$ciBuildProjectLog->created          = date('Y-m-d H:m:s', time());
                $ciBuildProjectLog->created          = Carbon::now();
            }
                $ciBuildProjectLog->save();

                //  查询结果
                $interval = 10000;  // 10 ms
                $tickCount = ($timeout * 1000 * 1000) / $interval;

                while ($tickCount > 0) {
                    $ciBuildProjectLog = CiBuildProjectLog::where('gaea_build_id', $gaeaBuildId)
                        ->where('jenkins_job_name', $jobName)
                        ->first();

                    if (is_null($ciBuildProjectLog)) {
                        LogUtil::error('ciBuildProjectLog null', ['jobName' => $jobName, 'gaea_build_id' => $gaeaBuildId, 'params' => $params]);
                        return false;
                    }

                    //if ($ciBuildProjectLog->status == self::JENKINS_JOB_STATUS_SUCCESS) {
                    if ($ciBuildProjectLog->status == CiCdConstants::JENKINS_JOB_STATUS_SUCCESS) {
                        return true;
                    }

                    //if ($ciBuildProjectLog->status == self::JENKINS_JOB_STATUS_FAILURE) {
                    if ($ciBuildProjectLog->status == CiCdConstants::JENKINS_JOB_STATUS_FAILURE) {
                        return false;
                    }

                    usleep($interval);
                    $tickCount--;
                }

                // 超时
                if ($tickCount <= 0) {
                    return false;
                }
            }

            return $result;
        } catch (RuntimeException $e) {
            LogUtil::error('Jenkins launch job exception', [$e->getMessage()]);
            return false;
        }
    }/*}}}*/
}
