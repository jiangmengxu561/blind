<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 示例接口
 */
class Logistics extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['logistics', 'information'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];
//
    public function logistics(){
        defined('EBusinessID') or define('EBusinessID', '1779604');//请到快递鸟官网申请http://kdniao.com/reg
        //电商加密私钥，快递鸟提供，注意保管，不要泄漏
        defined('AppKey') or define('AppKey', 'dc292517-cd19-4a07-baa8-dab0842e3115');//请到快递鸟官网申请http://kdniao.com/reg
        //请求url
        defined('ReqURL') or define('ReqURL', 'https://api.kdniao.com/api/EOrderService');

        //构造电子面单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = "SF";
        $eorder["OrderCode"] = "PM201604062341";
        $eorder["PayType"] = 1;
        $eorder["ExpType"] = 1;

        $sender = [];
        $sender["Name"] = "李先生";
        $sender["Mobile"] = "18888888888";
        $sender["ProvinceName"] = "李先生";
        $sender["CityName"] = "深圳市";
        $sender["ExpAreaName"] = "福田区";
        $sender["Address"] = "赛格广场5401AB";

        $receiver = [];
        $receiver["Name"] = "李先生";
        $receiver["Mobile"] = "18888888888";
        $receiver["ProvinceName"] = "李先生";
        $receiver["CityName"] = "深圳市";
        $receiver["ExpAreaName"] = "福田区";
        $receiver["Address"] = "赛格广场5401AB";

        $commodityOne = [];
        $commodityOne["GoodsName"] = "其他";
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;

//调用电子面单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);

//$jsonParam = JSON($eorder);//兼容php5.2（含）以下

        echo "电子面单接口提交内容：<br/>".$jsonParam;
        $jsonResult = $this->submitEOrder($jsonParam);
        echo "<br/><br/>电子面单提交结果:<br/>".$jsonResult;

//解析电子面单返回结果
        $result = json_decode($jsonResult, true);
        echo "<br/><br/>返回码:".$result["ResultCode"];
        if($result["ResultCode"] == "100") {
            echo "<br/>是否成功:".$result["Success"];
        }
        else {
            echo "<br/>电子面单下单失败";
        }

    }

//-------------------------------------------------------------

    /**
     * Json方式 查询订单物流轨迹
     */
    function submitEOrder($requestData){
        $datas = array(
            'EBusinessID' => EBusinessID,
            'RequestType' => '1007',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, AppKey);
        $result=$this->sendPost(ReqURL, $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }


//    /**
//     *  post提交数据
//     * @param  string $url 请求Url
//     * @param  array $datas 提交的数据
//     * @return url响应返回的html
//     */
//    function sendPost($url, $datas) {
//        $temps = array();
//        foreach ($datas as $key => $value) {
//            $temps[] = sprintf('%s=%s', $key, $value);
//        }
//        $post_data = implode('&', $temps);
//        $url_info = parse_url($url);
//        if($url_info['port']=='')
//        {
//            $url_info['port']=80;
//        }
//        echo $url_info['port'];
//        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
//        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
//        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
//        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
//        $httpheader.= "Connection:close\r\n\r\n";
//        $httpheader.= $post_data;
//        $fd = fsockopen($url_info['host'], $url_info['port']);
//        fwrite($fd, $httpheader);
//        $gets = "";
//        $headerFlag = true;
//        while (!feof($fd)) {
//            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
//                break;
//            }
//        }
//        while (!feof($fd)) {
//            $gets.= fread($fd, 128);
//        }
//        fclose($fd);
//
//        return $gets;
//    }


//物流信息
    public function information(){
        $courierNumber= $this->request->param('courierNumber');
        $LogisticCode = $this->request->param('LogisticCode');
        $LogisticCode1 = Db::name('kuaicom')->where('id',$LogisticCode)->value('code');

        defined('EBusinessID') or define('EBusinessID', '1780248');//请到快递鸟官网申请http://kdniao.com/reg
        //电商加密私钥，快递鸟提供，注意保管，不要泄漏
        defined('AppKey') or define('AppKey', 'cbde3b54-28ac-4724-aa9c-5f73c2d6b079');//请到快递鸟官网申请http://kdniao.com/reg
        //请求url
        defined('ReqURL') or define('ReqURL', 'https://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx');

        $requestData=
            [
                'OrderCode'=>'',
                'ShipperCode'=>$LogisticCode1,
                'LogisticCode'=> $courierNumber,
            ];

        $requestData1 = json_encode($requestData);

//            "{'OrderCode':'','ShipperCode':'STO','LogisticCode':'773079102643512'}";

        $datas = [
            'EBusinessID' => EBusinessID,
            'RequestType' => '8001',
            'RequestData' => urlencode($requestData1) ,
            'DataType'    => 2,

        ];
        $datas['DataSign'] = $this->encrypt($requestData1, AppKey);

//        print_r($datas);die;
        $result=$this->sendPost(ReqURL, $datas);
        $this->success('success',json_decode($result));
    }
    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    public function sendPost($url, $datas) {
        $postdata = http_build_query($datas);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;

    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    public function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
}