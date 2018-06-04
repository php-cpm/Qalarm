<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'user';

    public function devices()
    {
        return $this->hasMany('App\Models\Device', 'userid', 'id');
    }
    
    public function cars()
    {
        return $this->hasMany('App\Models\CarInfo', 'ownerid', 'id');
    }
}
