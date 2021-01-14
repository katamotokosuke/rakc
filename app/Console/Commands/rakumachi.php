<?php


namespace App\Console\Commands;

define("PHP_QUERY_LIB", "phpQuery\phpQuery\phpQuery.php");
require PHP_QUERY_LIB;

use Illuminate\Console\Command;
use \phpQuery;



class rakumachi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:crawl';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        //ファイルにクロールした分を保存する前に現時点のファイルの行数を変数に保存
        $filename = "file.txt";
        $before = count( file ( $filename));

        //サイトからクロール
        $html = file_get_contents('https://www.rakumachi.jp/syuuekibukken/area/prefecture/dimAll/?pref=&gross_from=&price_to=');
        $names = array();
        $dimensions = array();
        $prices = array();
        $yields = array();

        //クロール結果を配列に追加
        $name_num = count(phpQuery::newDocument($html)->find(".propertyBlock__name"));
for($i=0; $i < $name_num; $i++){
    $dimensions[] = trim(phpQuery::newDocument($html)->find(".propertyBlock__dimension:eq($i)")->text());
    $names[] = trim(phpQuery::newDocument($html)->find(".propertyBlock__name:eq($i)")->text());
    $prices[] = trim(phpQuery::newDocument($html)->find(".price:eq($i)")->text());
    $yields[] = trim(phpQuery::newDocument($html)->find(".gross:eq($i)")->text());

}

         //クロールして得たデータとファイルの行数が同じでなかった場合のみファイル書き換え
         $fh = fopen($filename, "w");
         $row = count(file($filename));
        if($row !== $name_num){
            foreach(array_map(null, $dimensions, $names, $prices, $yields) as [$dimension, $name, $price, $yield]){
                    fwrite($fh, $dimension.",".$name.",".$price.",".$yield."\n");
            }
        }
        

        //ファイル書き込みが終わった段階のファイル行数を変数に保存
        $after = count( file ( $filename));
        fclose($fh);

        //クロール前後のファイルの行数を比較
        $plus = $after - $before;
        $minus = $after - $before;

        if($plus > 0){
            $message = '増加しました';
        }elseif($plus < 0){
            $message = '減少しました';
        }


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
        


    }
}