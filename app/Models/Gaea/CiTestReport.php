<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class CiTestReport extends Gaea
{
    protected $table = 'ci_test_report';
    protected $primaryKey = 'id';
    public $timestamps = false;

}
