<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class CiProjectChange extends Gaea
{
    protected $table = 'ci_project_change';
    protected $primaryKey = 'id';
    public $timestamps = false;

}
