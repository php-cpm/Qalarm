<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;
use App\Components\Jenkins\CiCdConstants;

class CiCdServerLog extends Gaea
{
    protected $table = 'ci_cd_server_log';
    protected $primaryKey = 'id';

    protected $fillable = [
        'project_id', 'project_name', 'gaea_build_id', 'deploy_id', 'deploy_step', 'deploy_action', 
        'deploy_status', 'started_time', 'host_name', 'host_type', 'host_ip', 'deploy_dir', 
        'host_is_test', 'host_cluster', 'parent_id',
    ];

    public function export ()
    {
        $stepLog = explode('|', $this->callback_step_log);

        $data = [
            "id"            => $this->id,
            "deploy_id"     => $this->deploy_id,
            "gaea_build_id" => $this->gaea_build_id,
            "project_id"    => $this->project_id,
            "project_name"  => $this->project_name,
            //"status"        => $this->status,
            //"status_desc"   => CiCdConstants::$deployStatusDesc[$this->status],
            "status"        => $this->deploy_status,
            "status_desc"      => CiCdConstants::getDeployStepACtionStatusDesc($this->deploy_step, $this->deploy_action, $this->deploy_status),
            //"status_desc"   => $this->deploy_step . '_' . $this->deploy_action . '_' . $this->deploy_status,
            "jid"           => $this->jid,
            "deploy_log"    => $this->deploy_log,
            "started_time"    => $this->started_time,
            "finished_time"    => $this->finished_time,

            "host_name"     => $this->host_name,
            "host_type"     => $this->host_type,
            "host_ip"       => $this->host_ip,
            "deploy_dir"    => $this->deploy_dir,
            "deploy_step"   => $this->deploy_step,
            //"callback_step_log"      => $stepLog, 
            "step_log"      => $stepLog, 
        ];

        return $data;
    }
}
