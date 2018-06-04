<?php
/**
* @file GitLabHelp.php
* @brief 
* @author 
* @version
* @date 
 */

namespace App\Components\GitLab;

class GitLabHook 
{

    private $webHookData = null;
    public function __construct($data)
    {
        $this->webHookData = json_decode($data);
    }

    public function getProjectId()
    {
        return $this->webHookData->project_id; 
    }

    public function getObjectKind()
    {
        return $this->webHookData->object_kind; 
    }
    
    public function getBefore()
    {
        return $this->webHookData->before; 
    }

    public function getAfter()
    {
        return $this->webHookData->after; 
    }

    public function getCheckoutSha()
    {
        return $this->webHookData->checkout_sha; 
    }
    

    public function getUserId()
    {
        return $this->webHookData->user_id; 
    }

    public function getUserName()
    {
        return $this->webHookData->user_name; 
    }

    public function getUserEmail()
    {
        return $this->webHookData->user_email; 
    }

    public function getRefBranch()
    {
        $branch = str_replace('refs/heads/', '', $this->webHookData->ref);
        return $branch; 
    }

    public function getTotalCommitsCount()
    {
        return $this->webHookData->total_commits_count; 
    }

    //commits 节点暂不解析
    public function getCommits()
    {
        return json_encode($this->webHookData->commits);
    }

    public function getRepository()
    {
        return $this->webHookData->repository; 
    }

    public function getProjectName()
    {
        //$projectName = $this->getRepository()->name ;
        //$projectName = strtolower($projectName) ;
        //$projectName = preg_replace('/[^a-z0-9]+/i','-',$projectName);
        //return $projectName;
        return $this->getRepository()->name;
    }

    public function getProjectDescription()
    {
        return $this->getRepository()->description;
    }
    
    public function getProjectHomePage()
    {
        return $this->getRepository()->homepage;
    }

    public function getProjectGitUrl()
    {
        return $this->getRepository()->git_http_url;
    }

    public function getProjectGitSshUrl()
    {
        return $this->getRepository()->git_ssh_url;
    }

    public function getGitlabWebSite() {
        //return $this->:
        return 'http://172.16.10.196:8360/';
    }
    
    ////response 节点
    //const RESPOSITORY = 'respository'; 
    //const PROJECT_NAME = 'name';
    //const URL = 'url';
    //const DESCRIPTION = 'descript';
    //const HOMEPAGE = 'homepage';
    //const GIT_HTTP_URL = 'git_http_url';
    //const GIT_SSH_URL = 'git_ssh_url';
    ////commits 节点暂时不解析
    //const COMMITS = 'commits'; 
    
}
