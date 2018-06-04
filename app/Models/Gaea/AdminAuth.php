<?php

namespace  App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class AdminAuth extends Gaea
{
    protected $table = 'admin_auth';

    public function export()
    {
        return $this;
    }
}
