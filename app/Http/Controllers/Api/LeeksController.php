<?php
namespace App\Http\Controllers\Api;

use App\Models\Fund;
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
        $order = request()->input('order') == 1 ? 'desc' : 'asc';
        $start_time = request()->input('start_time') ?? date('Y-m-d',strtotime('-1 month'));
        $end_time = request()->input('end_time') ?? date('Y-m-d',strtotime('-1 day'));
        $limit = request()->input('limit') ?? 0;
        $pos = request()->input('pos') ?? 10;
        $fund = new Fund();
        $total = $fund->count();
        $list = [];
        if ($total > 0){
            $sql = "select a.date,a.code,b.name,b.manager,b.fundScale,sum(a.worth) total_worth from leeks_fund_worth_detail a inner join leeks_fund b on a.code = b.code where a.date between :start_time and :end_time group by a.code order by total_worth $order limit :limit,:pos";
            $list = DB::select($sql, [':start_time' => $start_time,':end_time'=>$end_time,':limit'=>$limit,':pos'=>$pos]);
            $list = array_map('get_object_vars', $list);
        }
        return ['total'=>$total,'list'=>$list];
    }
}