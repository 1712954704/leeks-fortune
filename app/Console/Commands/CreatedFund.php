<?php

namespace App\Console\Commands;

use App\Models\Fund;
use App\Models\FundWorthDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Constants\Fund as ConstantsFund;

class CreatedFund extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
//    protected $signature = 'command:name';
    protected $signature = 'leeks:get_fund';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'created leek_fund';

    protected $limit_num = 50;

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
//        $this->created_fund_info();
        $this->batch_get_fund_detail();
    }

    /**
     * 批量获取基金详情
     */
    public function batch_get_fund_detail()
    {
        $url = ConstantsFund::FUND_LITTLE_BEAR_BATCH_DETAIL_LIST_GET;
        $fund = new Fund();
        $FundWorthDetail = new FundWorthDetail();
        $result_code = DB::select('select id,code from leeks_fund_worth_detail order by id desc limit 1');
        $result_code = array_map('get_object_vars', $result_code);
        $result_id = DB::select('select id,code from leeks_fund where code = :code',['code'=>$result_code[0]['code']]);
        $result_id = array_map('get_object_vars', $result_id);
        $limit = $result_id[0]['id']; // 初始值
        $start_time = '2021-12-26';
        $end_time = '2021-01-01';
        $count = $fund->count();
        $num = ceil($count/$this->limit_num);
        $data = [];
//        $time = date('Y-m-d H');
        $send_num = 0;

        for ($i = 1;$i < $num;$i++){
            if ($i > 1){
                $limit += $this->limit_num;
            }
            $result = DB::select('select id,code from leeks_fund order by id asc limit :limit,:offset', ['limit' => $limit,'offset'=>$this->limit_num]);
            $result = array_map('get_object_vars', $result);
            $string_code = implode(',',array_column($result,'code'));
            $option = '?code='.$string_code.'&startDate='.$start_time.'&endDate='.$end_time;
            $url .= $option;
            if (!($send_num%100) && $send_num){
                sleep(3600);
                $send_result = send($url,'get');
            }else{
                $send_result = send($url,'get');
            }

            if ($send_result['code'] == 200){
                $send_num ++;
                foreach ($send_result['data'] as $item){
                    // 历史净值信息
                    if (isset($item['netWorthData']) && is_array($item['netWorthData'])){
                        foreach ($item['netWorthData'] as $info){
//                            $get_info = DB::select('select id,code from leeks_fund_worth_detail where code = :code and date = :date', ['code' => $item['code'],'date'=>$info[0]]);
//                            if (!$get_info){
                                $data = [
                                    'code' => $item['code'],
                                    'date' => $info[0],    // 日期
                                    'nav' => round($info[1],4),     // 单位净值
                                    'worth' => round($info[2],4),   // 净值涨幅
                                ];
                                $FundWorthDetail->insert($data);
//                            }
                        }
                    }
                }
            }
        }
        echo 'done';
    }

    /**
     * 生成基金基本信息
    */
    public function created_fund_info()
    {
        $url = ConstantsFund::FUND_LITTLE_BEAR_ALL_GET;
        $result = send($url,'get');
        $fund = new Fund();
        try {
            foreach ($result['data'] as $item){
                $data = [
                    'code' => $item[0],
                    'abbr_name' => $item[1],
                    'name' => $item[2],
                    'type' => $item[3],
                    'full_name' => $item[4],
                ];
                $fund->insert($data);
            }
            var_dump('ok');
        }catch (\Exception $exception){
            var_dump($exception->getMessage()); die();
        }
    }
}
