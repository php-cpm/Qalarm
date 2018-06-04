<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Log;
use App\Components\Utils\LogUtil;
use Carbon\Carbon;


use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectMember;
use App\Models\Gaea\CiGitMember;
use App\Models\Gaea\AdminUser;

class setGitWebHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaea:set_git_webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '设置git WebHook';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        $gitWebsite = sprintf('%s/api/v3/', env('CI_GITLAB_URL')); // gitlab url
        $gitToken = env('CI_GITLAB_ADMIN_TOKEN'); //admin token

        $client = new \Gitlab\Client($gitWebsite); // change here
        $client->authenticate($gitToken, \Gitlab\Client::AUTH_URL_TOKEN); // change here

        $webSite = env('CI_GITLAB_WEBHOOK_URL'); 

        $pageIndex = 1;
        $pageSize = 200;
        //$projectAll = $client->api('projects')->all();
        $projectAll = $client->api('projects')->all($pageIndex, $pageSize); //git api 默认接口；数据会分页
        foreach ( $projectAll as $project ) {
            $projectId   = $project['id'];

            $hooks = $client->api('projects')->hooks($projectId);
            $isSet = false;
            foreach ( $hooks as $item ) {
                if ($item['url'] == $webSite) {
                    $isSet = true;
                    break; 
                }
            }

            if (!$isSet) {
                $client->api('projects')->addHook($projectId, $webSite);
            }
        }

        //$result = [];
        //foreach ( $projectAll as $project ) {
            //$projectId   = $project['id'];
            //$hooks = $client->api('projects')->hooks($projectId);
            //$result[] = $hooks;
        //}
    }/*}}}*/
}
