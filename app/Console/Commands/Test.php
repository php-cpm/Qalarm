<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use FFan\Qalarm\Qalarm;
use App\Models\Qalarm\Strategy;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature ='alarm:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试命令';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $s = new Strategy();
        $s->param1 = 1;
        $s->param2 = 1;
        $s->param3 = 100;
        $s->is_sms = 1;
        $s->is_email = 1;

        $s->save();


    }
}
