<?php


namespace App\Console\Commands;

require_once('phpQuery\phpQuery\phpQuery.php');
require_once('const.php');

use Illuminate\Console\Command;
use \phpQuery;


class Rakumachi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:crawl {--pref=}{--prop=}{--price=}{--yield=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '楽待サイトをクロールし、更新情報があればお知らせします。';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    const RakumachiUrl = 'https://www.rakumachi.jp/syuuekibukken/area/prefecture/dimAll/?page=';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        //オプションを受け取る
        $pref = $this->option('pref');
        $prop = $this->option('prop');
        $price = $this->option('price');
        $yield = $this->option('yield');

        //別ファイルの都道府県番号振り分け関数を実施
        $pft = new Prefecture();
        $pft->prefecture = $pref;
        $pft->getPrefNum();

        //別ファイルの種別番号振り分けを実施
        $proop = new Types();
        $proop->props = $prop;
        $proop->getPropNum();

        
        //配列に当てはまるかどうかをチェック
        $price_array = [300, 500, 800, 1000, 1500, 2000, 2500, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000];
        $search = array_search($price, $price_array);
        if(!is_bool($search)){
            $price_num = $price_array[$search];
        }elseif($price == ''){
            $price_num = '';
        }else{
            echo '--priceオプションの入力に誤りがあります。';
            return;
        }
        echo $price_num;

        if($yield == 3 || 5 || 8 || 12){
            $yield_num = $yield;
        }elseif($yield == ''){
            $yield_num = '';
        }else{
            echo '--yieldオプションの入力に誤りがあります。';
            return;
        }

        

        $names = array();
        $dimensions = array();
        $prices = array();
        $yields = array();


        if(isset($pft->option)){
            $option = $pft->option;
            $option_file = $pft->option_file;
        }elseif(isset($proop->option)){
            $option = $proop->option;
            $option_file = $proop->option_file;
        }elseif($pref == '' && $prop == '' && $yield == '' && $price == ''){
            $option = 'none';
            $option_file = 'all';
        }
        
        
        //ファイル作成
        $now = date("Y-m-d-H-i");
        $file = 'C:/xampp/htdocs/laravel/rakc/files/'.$option.'/'.$option_file.'/crawl_'.$now.'.txt';
        $file_folder = glob('C:/xampp/htdocs/laravel/rakc/files/'.$pft->option.'/'.$pft->option_file.'/*');
        touch($file);


        //サイトからクロール　ページネーションを巡回
        for($page_num = 1; $page_num <= 2; $page_num++){

            $url = self::RakumachiUrl.$page_num.'&pref='.$pft->pref_num.'&gross_from='.$yield_num.'&price_to='.$price_num.$proop->prop_num;
            $html = file_get_contents($url);
            
             //クロール結果を配列に追加
        $name_num = count(phpQuery::newDocument($html)->find(".propertyBlock__name"));
        for($i=0; $i < $name_num; $i++){
            $dimensions[] = trim(phpQuery::newDocument($html)->find(".propertyBlock__dimension:eq($i)")->text());
            $names[] = trim(phpQuery::newDocument($html)->find(".propertyBlock__name:eq($i)")->text());
            $prices[] = trim(phpQuery::newDocument($html)->find(".price:eq($i)")->text());
            $yields[] = trim(phpQuery::newDocument($html)->find(".gross:eq($i)")->text());
        
        }


        //ファイルに書き込み
        $fh = fopen($file, "a");
        foreach(array_map(null, $dimensions, $names, $prices, $yields) as [$dimension, $name, $price, $yield]){
            fwrite($fh, $dimension.",".$name.",".$price.",".$yield."\n");
    }

    //nextページボタンがなくなったらループから抜ける
    $page = phpQuery::newDocument($html)->find(".pagination .next")->text();
        if($page == ''){
            break;
        }
    
}

fclose($fh);
                 

        //既に同じ条件でクロールされていたら増減を求める
        if(!empty($file_folder)){
            $prev_file = end($file_folder);
            $prev_line = count(file($prev_file));

            if($name_num > $prev_line){
                $plus = $name_num - $prev_line;
                $message = '増加しました';
            }elseif($prev_line > $name_num){
                $plus = $prev_line - $name_num;
                $message = '減少しました';
            }else{
                //数に変わりなかったらファイルに書き込んで終わり
                return;
            }
        }else{
            //一つ目のファイルの場合
            $plus = $name_num;
            $message = '増加しました';
        }
        
                
        /*
        //LINE通知
        $channelToken = 'tFrOOXIVQ68Y2h3//fqQuU8pVnwNpc4LKEkTtUKk6wl2SJPmVJfAV48Qlt2kXlsmDcf8MB9oHVrSw8UImq2yap9uX4UlhUcw3Toefi5GTuetOMkQaYR8BQVB2ZJOeWklP+uL0E8IpSb+Ff5AjXSu8QdB04t89/1O/w1cDnyilFU=';
        $headers = [
            'Authorization: Bearer ' . $channelToken,
            'Content-Type: application/json; charset=utf-8',
        ];
        
        // POSTデータを設定してJSONにエンコード
        $post = [
            'to' => 'U9eb99f446cb2fbfa3ef31f79cd99e6cf',
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $plus.$message,
                ],
            ],
        ];
        $post = json_encode($post);
        
        // HTTPリクエストを設定
        $ch = curl_init('https://api.line.me/v2/bot/message/push');
        $options = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => true,
        ];
        curl_setopt_array($ch, $options);
        
        // 実行
        $result = curl_exec($ch);
        
        // エラーチェック
        $errno = curl_errno($ch);
        if ($errno) {
            echo('error:'.$errno);
            return;
        }

        // HTTPステータスを取得
        $info = curl_getinfo($ch);
        $httpStatus = $info['http_code'];
        
        $responseHeaderSize = $info['header_size'];
        $body = substr($result, $responseHeaderSize);
        
        // 200 だったら OK
        echo $httpStatus . ' ' . $body;
       
*/
        
    }

}


