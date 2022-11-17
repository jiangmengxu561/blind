<?php
namespace app\api\controller;

use app\common\controller\Api;
use think\Collection;
use think\Db;
use think\Config;
use think\Exception;
use app\common\model\Bill;

class Pays extends Collection{

    /**
     * notes :微信充值异步回调
     */
    public function vx_recharge_notify() {//
        wechatpay_log('========================================');
        wechatpay_log(date('Y-m-d H:i:s').'--充值异步回调开始：');
        $xml         = file_get_contents("php://input");
        $array_data  = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        wechatpay_log('充值异步返回信息：'.json_encode($array_data));
        if(($array_data['return_code']=='SUCCESS') && ($array_data['result_code']=='SUCCESS')){
            $transaction_id = $array_data['transaction_id']; //微信支付系统生成的订单编号
            $order_id = $array_data['out_trade_no'];         // 商城系统中的订单编号
            $total_fee = $array_data['total_fee']/100;
            // $result_code = $array_data['result_code'];       // 订单支付状态 SUCCESS/FAIL

            /*查询出订单*/
            $order = Db::name("user_recharge")->where(array("order_sn"=>$order_id, 'payment_status'=>'1','order_status'=>0))->find();
            if(!$order){
                // 订单信息不存在
                wechatpay_log('充值支付订单不存在 (订单号: '.$order_id.')');
                $return = false;
            } else {
                $time = time();
                Db::startTrans();
                try{
                    /*更新总订单支付状态*/
                    $orderup = [
                        "order_status"=>1,
                        "price"=>$total_fee,
                        "payment_time"=>$time,
                    ];
                    Db::name("user_recharge")->where('id', $order['id'])->update($orderup);
                    Db::name('user')->where('id',$order['user_id'])->setInc('money',$total_fee);
                    (new Bill())->add_bill($order['user_id'],'余额充值',$total_fee,1);

                    Db::commit();
                    wechatpay_log('订单 ('.$order_id.') 状态修改成功！');
                    $return = true;
                }catch (\Exception $e){
                    Db::rollback();
                    wechatpay_log('订单 ('.$order_id.') 状态修改失败！失败原因：'.$e->getMessage());
                    $return = false;
                }
            }
        }else{
            wechatpay_log('支付验证失败 (失败原因：'.$array_data['return_msg'].')');
            $return = false;
        }

        $arr['return_code'] = $return ? 'SUCCESS' : 'FAIL';
        $arr['return_msg']  = $return ? 'OK' : 'NO';

        $xml = '<xml>';
        foreach ($arr as $key=>$val) {
            if(is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        echo  $xml;

        wechatpay_log('充值异步回调结束');
        wechatpay_log('^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^');
    }

    /**
     * notes :微信购买视频异步回调
     */
    public function vx_goods_notify() {
        wechatpay_log('========================================');
        wechatpay_log(date('Y-m-d H:i:s').'--购买视频异步回调开始：');
        $xml         = file_get_contents("php://input");
        $array_data  = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        wechatpay_log('购买视频异步返回信息：'.json_encode($array_data));
        if(($array_data['return_code']=='SUCCESS') && ($array_data['result_code']=='SUCCESS')){
            $transaction_id = $array_data['transaction_id']; //微信支付系统生成的订单编号
            $order_id = $array_data['out_trade_no'];         // 商城系统中的订单编号
            $total_fee = $array_data['total_fee']/100;
            // $result_code = $array_data['result_code'];       // 订单支付状态 SUCCESS/FAIL

            /*查询出订单*/
            $order = Db::name("video_order")->where(array("order_sn"=>$order_id, 'payment_status'=>'1','order_status'=>0))->find();
            if(!$order){
                // 订单信息不存在
                wechatpay_log('购买视频支付订单不存在 (订单号: '.$order_id.')');
                $return = false;
            } else {
                $time = time();
                Db::startTrans();
                try{
                    /*更新总订单支付状态*/
                    $orderup = [
                        "order_status"=>1,
                        "payment_time"=>$time,
                    ];
                    Db::name("video_order")->where('id', $order['id'])->update($orderup);
                    (new Bill())->reward($order['user_id'],$order['price']);//分佣

                    Db::commit();
                    wechatpay_log('订单 ('.$order_id.') 状态修改成功！');
                    $return = true;
                }catch (\Exception $e){
                    Db::rollback();
                    wechatpay_log('订单 ('.$order_id.') 状态修改失败！失败原因：'.$e->getMessage());
                    $return = false;
                }
            }
        }else{
            wechatpay_log('支付验证失败 (失败原因：'.$array_data['return_msg'].')');
            $return = false;
        }

        $arr['return_code'] = $return ? 'SUCCESS' : 'FAIL';
        $arr['return_msg']  = $return ? 'OK' : 'NO';

        $xml = '<xml>';
        foreach ($arr as $key=>$val) {
            if(is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        echo  $xml;

        wechatpay_log('购买视频异步回调结束');
        wechatpay_log('^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^');
    }
    /**
     * notes : 支付宝充值异步通知
     */
    public function recharge_notify() {
//        alipay_log('========================================');
//        alipay_log(date('Y-m-d H:i:s').'--充值异步回调开始：');
//        alipay_log('充值支付异步返回信息：'.json_encode($_POST));
//        recording_log(date('Y-m-d H:i:s').'--充值支付宝支付统一下单信息：'.json_encode($_POST), 'alipays/alipays.txt');
        // $_POST = json_decode('{"gmt_create":"2022-08-19 17:22:25","charset":"utf-8","seller_email":"changanzuhao@163.com","subject":"\u966a\u73a9\u5145\u503c","sign":"oROAwVy\/VbKmi9Bl1WAenmkD11g5XrnoBAacPHnHeLOFhzYl4y1sMmWASsZE4+b9+MlEIOXJX5fsbRqo1e8LGy96M9At1WqwnM4RvwAL7BVf5uiiRbwSG10RuVAsmxg6+rC56tbLgVOCggof5hfKti6K0k7xs68mJoBjqTaVLCwwyKniHLFSdfugaMCLJVoOwoEtzbIRvi7CrJAI3qcf252lGp6CHT8hQgMmUR1RVg7r7vF8sMpcmfxfJjw077dIi1IGIjMyjnE5fhJRPkLDzrVx8UfSktW\/t3WWxuXkW1+EzEg0ynhYFpICoikm3TF7CQWKpOpa0J32NUrQvySDXA==","buyer_id":"2088422428554905","invoice_amount":"0.01","notify_id":"2022081900222172227054901458956016","fund_bill_list":"[{\"amount\":\"0.01\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.01","buyer_pay_amount":"0.01","app_id":"2021003144635054","sign_type":"RSA2","seller_id":"2088441851062713","gmt_payment":"2022-08-19 17:22:26","notify_time":"2022-08-19 17:25:07","version":"1.0","out_trade_no":"20220819172213727231","total_amount":"0.01","trade_no":"2022081922001454901403366443","auth_app_id":"2021003144635054","buyer_logon_id":"135****3848","point_amount":"0.00"}',true);

        if($_POST['trade_status']=='TRADE_FINISHED' || $_POST['trade_status']=='TRADE_SUCCESS' || $_POST['return_code']=='SUCCESS' && $_POST['result_code']=='SUCCESS') {
            //处理更新订单状态等相关逻辑
            $transaction_id = $_POST['trade_no'];      // 支付宝系统中的订单编号
            $out_trade_no   = $_POST['out_trade_no'];  // 商户系统中的订单编号
            $total_fee      = $_POST['total_amount'];  // 支付价格

            /*查询出订单*/
            $order = Db::name("user_recharge")->where(array("order_sn"=>$out_trade_no, 'payment_status'=>'2','order_status'=>0))->find();
            if($order){
                $time = time();
                Db::startTrans();
                try{
                    /*更新总订单支付状态*/
                    $orderup = [
                        "order_status"=>1,
                        "price"=>$total_fee,
                        "payment_time"=>$time,
                    ];
                    Db::name("user_recharge")->where('id', $order['id'])->update($orderup);
                    Db::name('user')->where('id',$order['user_id'])->setInc('money',$total_fee);
                    (new Bill())->add_bill($order['user_id'],'余额充值',$total_fee,1);
                    Db::commit();
//                    alipay_log('订单(order_id='.$out_trade_no.') 状态修改成功！');
                    echo 'success';
                }catch (\Exception $e){
                    Db::rollback();
//                    alipay_log('订单(order_id='.$out_trade_no.') 状态修改失败！失败原因：'.$e->getMessage());
                    echo "fail";
                }
            } else {
                // 未找到订单信息
//                alipay_log('充值支付订单不存在 (订单号: '.$out_trade_no.')');
                echo 'fail';
            }
        }
        //程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，支付宝服务器会不断重发通知，直到超过24小时22分钟。一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）；

//        alipay_log('充值支付异步回调结束');
//        alipay_log('^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^');
    }
}