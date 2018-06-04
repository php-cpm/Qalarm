<?php
namespace App\Models\Gaea;

use App\Components\Jenkins\CiCdConstants;
use Illuminate\Database\Eloquent\Model;

class CiBuildProjectLog extends Gaea
{
    protected $table = 'ci_build_project_log';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function export ()
    {/*{{{*/
        $jenkinsStatsuDesc = '';
        if ($this->jenkins_job_name == 'build_'.$this->project_name) {
            $jenkinsStatsuDesc = '构建';
        }

        if ($this->jenkins_job_name == 'checkcode_'.$this->project_name) {
            $jenkinsStatsuDesc = '代码检查';
        }

        foreach (CiCdConstants::$JenkinsJobStatusDesc as $key => $item) {
            if ($this->status == $key) {
                $jenkinsStatsuDesc = $JenkinsJobStatusDesc.$item;
                break;
            }
        }
        return $jenkinsStatsuDesc; 
    }/*}}}*/

}
