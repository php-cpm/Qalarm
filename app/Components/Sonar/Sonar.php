<?php
namespace App\Components\Sonar;

use App\Components\Utils\HttpUtil;
use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\Constants;

use Log;

use App\Components\Jenkins\JenkinsDslHelper;
use App\Components\Jenkins\JenkinsHelp;

class Sonar
{
    // sonar地址
    protected $baseUrl;

    protected $timeout = 30;

    // 常量定义 /*{{{*/
    const SONAR_COMPLEXITY    =  1;
    const SONAR_COMMENT       =  2;
    const SONAR_DUPLICATION   =  3;
    const SONAR_ISSUES        =  4;
    const SONAR_DEBT          =  5;
    const SONAR_TESTS         =  6;
    const SONAR_SEP           = 'xxxxx';

    const SONAR_ISSUES_LEVEL  = [
        'blocker',
        'critical',
        'major',
        'minor',
        'info'
    ];

    public static $sonarMetrices = [
        self::SONAR_COMPLEXITY   => [
            'complexity',
            'class_complexity',
            'file_complexity',
            'function_complexity',
        ],
        self::SONAR_COMMENT      => [
            'comment_lines',
            'comment_lines_density',
            'public_documented_api_density',
            'public_undocumented_api',
        ],
        self::SONAR_DUPLICATION  => [
            'duplicated_blocks',
            'duplicated_files',
            'duplicated_lines',
            'duplicated_lines_density',
        ],
        self::SONAR_ISSUES      => [
            'new_violations',
            'new_xxxxx_violations',
            'violations',
            'xxxxx_violations',
            'false_positive_issues',
            'open_issues',
            'confirmed_issues',
            'reopened_issues',
            'weighted_violations',
            'violations_density',
        ],
        self::SONAR_DEBT       => [
            'sqale_index',
            'sqale_debt_ratio',
            'new_sqale_debt_ratio',
        ],
        self::SONAR_TESTS     => [
            'branch_coverage',
            'new_branch_coverage',
            'branch_coverage_hits_data',
            'conditions_by_line',
            'covered_conditions_by_line',
            'coverage',
            'new_coverage',
            'line_coverage',
            'new_line_coverage',
            'coverage_line_hits_data',
            'lines_to_cover',
            'new_lines_to_cover',
            'skipped_tests',
            'uncovered_conditions',
            'new_uncovered_conditions',
            'uncovered_lines',
            'new_uncovered_lines',
            'tests',
            'test_execution_time',
            'test_errors',
            'test_failures',
            'test_success_density',
        ]
    ];/*}}}*/

    private function getMetrices($metricNames = [])
    {/*{{{*/
        if (empty($metricNames)) {
            $metricNames = array_keys(static::$sonarMetrices);
        }

        $metricKeys = [];
        foreach ($metricNames as $key) {
            $metrics = static::$sonarMetrices[$key];
            foreach ($metrics as $metric) {
                // 存在
                if (strpos($metric, static::SONAR_SEP) !== false) {
                    foreach (static::SONAR_ISSUES_LEVEL as $level) {
                        $metricKeys[]  = str_replace(static::SONAR_SEP, $level, $metric);
                    }
                } else {
                    $metricKeys[] = $metric;
                }
            }
        }

        return join(',', $metricKeys);
    }/*}}}*/

    public function __construct()
    {/*{{{*/
        $this->baseUrl = env('CI_SONAR_URL');
    }/*}}}*/

    /**
     * @brief fetchProjectMetrices 获取项目的度量值
     * @param $projectName
     * @param $metric 默认为全部，可以传递种类的数组
     * @return []
     */
    public function projectMetrics($projectName, $metric = [])
    {/*{{{*/
        $interface = 'api/resources';
        $post = [];
        $post['format']    = 'json';
        $post['resource']  = $projectName;
        $post['metrics']   = $this->getMetrices($metric);
//        $post['depth']     = -1;

        $url = sprintf('%s/%s', $this->baseUrl, $interface);

        $raw = HttpUtil::httpRequest($url, $post, [], $this->timeout);

        $res = json_decode($raw, true);

        dd($res);
        if (is_null($res)) {
            Log::notice(__class__ . ' api response error', $res);
            return false;
        }

        return $res;
    }/*}}}*/

    /**
     * @brief createAndUpdateJobs 
     * @param $projectName 项目名
     * @param $repo  仓库名
     * @param $branch 分支
     *
     * @return false | true
     */
    public function createAndUpdateJobs($projectName, $repo, $branch)
    {/*{{{*/
        $csmDsl = JenkinsDslHelper::dslSCMBlock($repo, $branch, env('CI_JENKINS_CREDENTIAL'));
        $sonarDsl = JenkinsDslHelper::sonarJobsBlock($branch);

        $dsl = JenkinsDslHelper::dslJobBlock($projectName, [$csmDsl, $sonarDsl], JenkinsDslHelper::GAEA_STEP_CODE);

        $result = JenkinsHelp::gaeaCreateJenkinsJob2($projectName, $dsl);

        return $result;
    }/*}}}*/

    /**
     * @brief launchSonarJobs 执行job
     *
     * @param $projectName
     * @param $branch
     * @param $gitHead
     * @param $checkDirs
     *
     * @return 
     */
    public function launchJobs($projectName,
                               $branch    = 'master',
                               $gitHead   = '',
                               $checkDirs = 'src',
                               $gaeaBuildId = ''
                           )
    {/*{{{*/
        $jobName = JenkinsDslHelper::$gaeaSteps[JenkinsDslHelper::GAEA_STEP_CODE]['name'] .'_'.$projectName;

        $params = JenkinsDslHelper::getSonarJobParams();
        $vaules = [$projectName, $projectName, $gitHead, $checkDirs, $branch];
        $post = array_combine($params, $vaules);

        $result = JenkinsHelp::launchJenkinsJobs($jobName, $post, $gaeaBuildId);
        if (!$result) {
            LogUtil::error('launchJob',['launchjob is error'.$jobName]);
        }

        return $result;
    }/*}}}*/
}
