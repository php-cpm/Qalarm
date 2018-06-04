<?php
namespace App\Models\Common;

/*
 * 与laravel eloquent 兼容的job 数据结构
 */
use App\Components\Utils\MethodUtil;

class BaseJobObject
{
    public $table;
    public $id;

    public function __construct()
    {
        $this->id = MethodUtil::getUniqueId();
    }
}
