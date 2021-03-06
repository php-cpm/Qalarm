<?php

namespace App\Models\Qalarm;

use Illuminate\Database\Eloquent\Model;

class Project extends Qalarm
{
    public $timestamps = true;
    protected $table = 'project';

    public function strategy()
    {
        return $this->hasOne('App\Models\Qalarm\Strategy', 'id', 'strategy_id');
    }

    public function modules()
    {
        return $this->hasMany('App\Models\Qalarm\Module', 'project_id', 'id');
    }

    public  function  export()
    {
        $s = $this->strategy;
        $this->strategyDesc = sprintf("连续(%s)次(%s)分钟内(%s)次 生效时间:%s点->%s点", $s->param1, $s->param2, $s->param3, $s->valid_start, $s->valid_end);
        $this->statusDesc   = ($this->status == 1 ? '开启' : '关闭');
        $this->testGraphStatusDesc   = ($this->test_graph_status == 1 ? '开启' : '关闭');
        $this->testAlarmStatusDesc   = ($this->test_alarm_status == 1 ? '开启' : '关闭');
        return $this;
    }
}
