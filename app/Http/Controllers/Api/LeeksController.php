<?php
namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Medoo\Medoo;

class LeeksController extends BaseController
{
    /**
     * 基金查询
     *
    */
    public function search_leeks()
    {
        $start_time = '2021-12-01';
        $end_time = '2022-01-01';
        $limit = 0;
        $pos = 10;
        $order = 'asc';
//        $sql = 'select a.code,b.name,b.manager,b.fundScale,sum(a.worth) total_worth from leeks_fund_worth_detail a inner join leeks_fund b on a.code = b.code where a.date between :start_time and :end_time group by a.code order by total_worth :order limit :limit,:pos';

        $sql = "select a.code,b.name,b.manager,b.fundScale,sum(a.worth) total_worth from leeks_fund_worth_detail a inner join leeks_fund b on a.code = b.code where a.date between '2021-12-01' and '2022-01-01' group by a.code order by total_worth asc limit 0,10";
//        $list = DB::select($sql, ['start_time' => $start_time,'end_time'=>$end_time,':order'=>$order,'limit'=>$limit,'pos'=>$pos]);
        $list = DB::select($sql);
//        $list = array_map('get_object_vars', $list);
        var_dump($list);die();
//        $sql = DB::table('my_table')->select()->tosql();
        // 初始化配置
        $database = new medoo([
            'database_type' => 'mysql',
            'database_name' => 'leeks',
            'server' => '47.100.172.170',
            'username' => 'xuzhen',
            'password' => 'xuzhen',
            'charset' => 'utf8'
        ]);

        // 插入数据示例
//        $database->insert('account', [
//            'user_name' => 'foo',
//            'email' => 'foo@bar.com',
//            'age' => 25,
//            'lang' => ['en', 'fr', 'jp', 'cn']
//        ]);

        $list = $database->query($sql);
        var_dump($list);die();

        $data = [];
        return $data;
    }
}