<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'device';

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id', 'userid');
    }
}
