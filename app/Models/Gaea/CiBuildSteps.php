<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class CiBuildSteps extends Gaea
{
    protected $table = 'ci_build_steps';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
