<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class CiJenkinsJobs extends Gaea
{
    protected $table = 'ci_jenkins_jobs';
    protected $primaryKey = 'id';
    public $timestamps = false;


}
