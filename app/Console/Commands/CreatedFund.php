<?php

namespace App\Console\Commands;

use App\Models\Fund;
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
        $url = ConstantsFund::FUND_LITTLE_BEAR_ALL_GET;
        var_dump($url);die();
//        $this->created_fund_info();
    }

    /**
     * 批量获取基金详情
     */
    public function batch_get_fund_detail()
    {
        $url = ConstantsFund::FUND_LITTLE_BEAR_BATCH_DETAIL_GET;
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
