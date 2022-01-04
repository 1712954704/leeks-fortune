<?php

namespace App\Console\Commands;

use App\Models\Fund;
use App\Models\FundErrorLog;
use App\Models\FundWorthDetail;
use App\Models\RequestLog;
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
    protected $signature = 'leeks:get_fund {type=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'created leek_fund';

    protected $send_num;
    protected $limit_num = 200;
    protected $url;
    protected $type;        // 1=默认全部更新 2=只更新detail详情表中没有的基金(在fund表中有)
    protected $start_time;
    protected $end_time;
    protected $fund;
    protected $FundWorthDetail;
    protected $RequestLog;
    protected $FundErrorLog;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->fund = new Fund();
        $this->FundWorthDetail = new FundWorthDetail();
        $this->RequestLog = new RequestLog();
        $this->FundErrorLog = new FundErrorLog();
        $this->send_num = 0;
//        $this->start_time = '2021-01-01';
        $this->start_time = date('Y-m-d',strtotime('-1 month'));
        $this->end_time = date('Y-m-d',strtotime('-1 day'));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->type = $this->argument('type');
//        $this->created_fund_info();
        $this->batch_get_fund_detail();
    }

    /**
     * 批量获取基金详情
     */
    public function batch_get_fund_detail()
    {
        switch ($this->type){
            case 1:
                // 初始化值
                $limit = 0; // 初始值
                $count = $this->fund->count();
                $num = ceil($count/$this->limit_num);
                $this->send_num;
                for ($i = 1;$i <= ($num+1);$i++) {
                    if ($i > 1) {
                        $limit += $this->limit_num;
                    }
                    $result = DB::select('select id,code from leeks_fund order by id asc limit :limit,:offset', ['limit' => $limit, 'offset' => $this->limit_num]);
                    $result = array_map('get_object_vars', $result);
                    $string_code = implode(',', array_column($result, 'code'));
                    $this->get_leeks_details($string_code);
                }

                // 查找漏写数据补充
                $limit = 0; // 初始值
                $sql_empty_count = 'select count(*) total from leeks_fund a where a.code not in (select a.code from leeks_fund_worth_detail a GROUP BY a.code)';
                $result_total = DB::select($sql_empty_count);
                $result_total = array_map('get_object_vars', $result_total);
                if ($result_total[0]['total'] > 0){
                    $num = ceil($result_total[0]['total']/$this->limit_num);
                    for ($i = 1;$i <= ($num+1);$i++) {
                        if ($i > 1) {
                            $limit += $this->limit_num;
                        }
                        $sql_empty = 'select a.code from leeks_fund a where a.code not in (select a.code from leeks_fund_worth_detail a GROUP BY a.code) order by id asc limit :limit,:offset';
                        $result = DB::select($sql_empty, ['limit' => $limit, 'offset' => $this->limit_num]);
                        $result = array_map('get_object_vars', $result);
                        $string_code = implode(',', array_column($result, 'code'));
                        $this->get_leeks_details($string_code);
                    }
                }
                break;
            case 2:
                // 查找漏写数据补充
                $limit = 0; // 初始值
                $sql_empty_count = 'select count(*) total from leeks_fund a where a.code not in (select a.code from leeks_fund_worth_detail a GROUP BY a.code)';
                $result_total = DB::select($sql_empty_count);
                $result_total = array_map('get_object_vars', $result_total);
                if ($result_total[0]['total'] > 0){
                    $num = ceil($result_total[0]['total']/$this->limit_num);
                    for ($i = 1;$i <= ($num+1);$i++) {
                        if ($i > 1) {
                            $limit += $this->limit_num;
                        }
                        $sql_empty = 'select a.code from leeks_fund a where a.code not in (select a.code from leeks_fund_worth_detail a GROUP BY a.code) order by id asc limit :limit,:offset';
                        $result = DB::select($sql_empty, ['limit' => $limit, 'offset' => $this->limit_num]);
                        $result = array_map('get_object_vars', $result);
                        $string_code = implode(',', array_column($result, 'code'));
                        $this->get_leeks_details($string_code);
                    }
                }
                break;
            default:
                break;
        }
        echo 'done'.PHP_EOL;
        exit();
    }

    /**
     * 基金查询并请求数据
     * @param string $string_code
     * @return bool
    */
    public function get_leeks_details($string_code)
    {
        $this->url = ConstantsFund::FUND_LITTLE_BEAR_BATCH_DETAIL_LIST_GET;
        $option = '?code='.$string_code.'&startDate='.$this->start_time.'&endDate='.$this->end_time;
        $this->url .= $option;
        if (!($this->send_num%100) && $this->send_num > 0){
            sleep(3600);
            $send_result = send($this->url,'get');
        }else{
            $send_result = send($this->url,'get');
        }
//            file_put_contents('result.txt',json_encode($send_result,256));die();
        // 写入日志
        $log_data = [
            'result' => json_encode($send_result,JSON_UNESCAPED_UNICODE),
        ];
        try {
            $this->RequestLog->insert($log_data);
            if ($send_result['code'] == 200) {
                $this->send_num++;
                $this->set_leeks_detail($send_result['data']);
            }
        }catch (\Exception $exception){
            // 写入错误日志
            $error_log_data = [
                'msg' => $exception->getMessage(),
            ];
            $this->FundErrorLog->insert($error_log_data);
        }
        return true;
    }


    /**
     * 分析data并写入基金信息detail
     * @param array $send_data
     * @param object $FundWorthDetail
     * @return bool
    */
    public function set_leeks_detail($send_data)
    {
        foreach ($send_data as $item){
            // 更新基金信息(经理,规模)
            $length = strpos($item['fundScale'],'亿') ?: false;
            if ($length){
                $fundScale = substr($item['fundScale'],0,$length);
            }else{
                $fundScale = $item['fundScale'];
            }
            DB::update('update leeks_fund set manager = :manager, fundScale = :fundScale,buyRate = :buyRate where code = :code', [':manager'=>$item['manager'],':fundScale'=>$fundScale,':buyRate'=>$item['buyRate'],':code' => $item['code']]);
            // 历史净值信息
            if (isset($item['netWorthData']) && is_array($item['netWorthData'])){
                foreach ($item['netWorthData'] as $info){
                    $get_info = DB::select('select id,code from leeks_fund_worth_detail where code = :code and date = :date', [':code' => $item['code'],':date'=>$info[0]]);
                    // 不存在则写入,存在则更新
                    if (!$get_info){
                        $date = $info[0].' 00:00:00';
                        $data = [
                            'code' => $item['code'],
                            'date' => $date,    // 日期
                            'nav' => round($info[1],4),     // 单位净值
                            'worth' => round($info[2],4),   // 净值涨幅
                        ];
                        $this->FundWorthDetail->insert($data);
                    }else{
                        DB::update('update leeks_fund_worth_detail set nav = :nav, worth = :worth where code = :code and date = :date', [':nav'=>round($info[1],4),':worth'=>round($info[2],4),':code' => $item['code'],':date'=>$info[0]]);
                    }
                }
            }
        }
        return true;
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
