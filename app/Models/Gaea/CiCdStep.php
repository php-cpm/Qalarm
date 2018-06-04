<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class CiCdStep extends Gaea
{
    protected $table = 'ci_cd_step';
    protected $primaryKey = 'id';

    protected $fillable = [
        'deploy_id', 'gaea_build_id', 'project_id', 'project_name', 'deploy_step', 'deploy_action',
        'deploy_status', 'user_id', 'user_name', 'started_time'
    ];
}
