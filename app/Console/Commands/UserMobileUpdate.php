<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

use App\Models\Gaea\AdminUser;

class UserMobileUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaea:usermobile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新用户的电话信息';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $list = app('notice')->getDindinUsers();
        foreach ($list as $mobile => $user) {
            $admin = AdminUser::where('mail', $user['email'])->first();
            if (!is_null($admin) && $admin->mobile != $mobile) {
                $admin->mobile = $mobile;
                $admin->save();
            }
        }
    }
}
