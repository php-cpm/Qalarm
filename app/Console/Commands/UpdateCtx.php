<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Qalarm\Monitor;

class UpdateCtx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature ='alarm:updatectx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新公司员工';

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
        $depts = [32169, 1357, 32171, 32162, 1365, 1358, 20634, 20640];
        $members = "linyijin,yaoming,caozhenhui,dengyechang,duyeguang,fuhao14,gaozhichao,guotianlong,hewei48,huangdong10,huangyuling7,jinbing,jinlingyun,likuifeng,linyijin,liuyuhui6,lizhi54,llikuifeng,lujingyu1,luoshuisheng,mabiao,machael1015,mingtingfeng,nijun14,panshupeng,peitao,pengyawei,ranjingfu,shenjian12,shiguifeng,shiliang8,sunhao27,tanghong,taoning,tongxiaobo,wangbo124,wangmin84,wangnan71,wangwenhua8,wangyan208,wanxiang9,wudawei6,xiehaiduo,xuyan,xuyan61,yuanchengming,yuanjunxia,yuliming1,zhangfan70,zhangjunbiao,zhangjunbiao1,zhanglianghao,zhangmiaomiao9,zhangyun,zhangyun35,zhaoshaofeng,zhaozhihui5,zhengyaru,zhongchunya,zhouhaiyang6,lichengzhen,heqinglong,liuxiaowei14,lvjishu,mapeng,qiaochuanbei,renyiguang,wangzhenyu20,yanhongming,yuandunbin,zhaobiao3,zhouxiang25,zongfuxiang,caixingbao,difangfang,dengjiankai,huangdong10,lichongxin,licaiyi,lujun35,lijianlin8,liliucan,limeng96,nimengjing,songyancheng,wangmengtong1,wuyufeng7,yuandunbin,yangliying6,yanglingyun,yangnaochun,yanru3,zhaobenbing,zhoudequan1,zhengqing8,zhangyang131,liguanyang,shenjian14,wangwenbin14,pengjiang1";

        $ours = explode(',', $members);
        $ours = array_unique($ours);

        $db = new \SQLite3("./data/qalarm/userdata.db");
        $results = $db->query('select u.UserId,u.CnUserName,u.EnUserName,u.UserCode,u.Sex,u.Tel,u.Phone,d.DeptId from UserDeptInfoTb as d, UserInfoTb as u where u.UserId=d.UserId;');
        while ($row = $results->fetchArray()) {
            $UserId = intval($row["UserId"]);
            $DeptId = intval($row["DeptId"]);
            $UserCode = "".$row["UserCode"];
            $CnUserName = "".$row["CnUserName"];
            $EnUserName = "".$row["EnUserName"];
            $Sex = intval($row["Sex"]);
            $Tel = "".$row["Tel"];
            $Mobile = intval($row["Phone"]);

            print $UserCode . '|' . $DeptId . '|'. $Mobile . "\n";
            continue;

            if (!in_array($DeptId, $depts)) {
                if (!in_array($UserCode, $ours)) {
                    continue;
                }
            }



            $monitor = Monitor::where('username' , $UserCode)->first();

            if (is_null($monitor)) {
                $monitor = new Monitor();
            }

            $monitor->username = $UserCode;
            $monitor->real_name = $CnUserName;
            $monitor->mail = $UserCode.'@wanda.cn';
            $monitor->mobile = $Mobile;
            $monitor->status = 1;
            $monitor->save();
        }
    }
} 
