<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
/**
 * 示例接口
 */
class Alipay extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['recharge_rules', 'recharge','apijilu','pay','notify'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];
    protected $config = [
        'app_id' => '2021003161615582',
        'notify_url' => 'http://mhe.yxbnet.cn/api/Alipay/notify',
        'return_url' => 'https://openapi.alipay.com/gateway.do',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkZODwKLcq0yVDPcYboZ6ArBDlya1XjWzFpy1okkrg1XvXKrZTV9wcCm9qFHXmvGaI9ZEch0KGOHALCxfEyCyxT82rQqN1ZO1oguQpRDMmUb0ahtmijXL3Z/i4G8NyqJnzB43QggXP1KKZLj/aWNzCzo1g4N/VQapGIRbira7H7jnKZaw0RkSV1If4LzQkv6G/qOeerVmLZpR5bJroULPiPPZUqgVMsee19Yd/EJ17gBL3ZjtebiRv8R4WcHnzVbiJHAG4+fUBjsbWtGWg91xrogm6PgewkS2NopkTOIKgO27troZbcEv9L2ZvTCwht92ie+t+U2aaRTlZ0z+HcTBDQIDAQAB',
        // 加密方式： **RSA2**
        'private_key' => 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCRk4PAotyrTJUM9xhuhnoCsEOXJrVeNbMWnLWiSSuDVe9cqtlNX3BwKb2oUdea8Zoj1kRyHQoY4cAsLF8TILLFPzatCo3Vk7WiC5ClEMyZRvRqG2aKNcvdn+Lgbw3KomfMHjdCCBc/UopkuP9pY3MLOjWDg39VBqkYhFuKtrsfuOcplrDRGRJXUh/gvNCS/ob+o556tWYtmlHlsmuhQs+I89lSqBUyx57X1h38QnXuAEvdmO15uJG/xHhZwefNVuIkcAbj59QGOxta0ZaD3XGuiCbo+B7CRLY2imRM4gqA7bu2uhltwS/0vZm9MLCG33aJ7635TZppFOVnTP4dxMENAgMBAAECggEAMFrok69FT10j0WeuZOAkXQpBmU6RRhbeQu+Q4bQeUQTVelmBztKZ3Zfv2J4+2MfR5H+Cwsjklrk+BS5D8m2VFDHYLohn7n6fAZGH3VyEKZHJFAm/+L6/1gEK8nPRB/MEOWf8AOIBHtaDA8vzgwU2rI8MQYAHZq3Ms0kbwyrJY1Kd6jOOD6Lcyp3koBahBDJRLI5WuPzrZjDQmL/6+uxSo/3jnhCw3pi3+Zw6r52GBDqQtys8feB1uzfGGgk3Ak2Ehra4MInv31qqme7XqKrW+pt9nqio2pI+9kTjNi79jvZE/skmmR/yYqwLSStYeORxTqy1GL7bwW++BM/YWER3IQKBgQDXWXHkypUikdZdslS5H+trFVex+hWLkRtKmd4h+CW1SVQqLkJ1Bbz2CSgCqLWtev02rLkY2DR4HfRHTh8nq0CRhNyrxlJbPo54z7chlfWR3jaKfIv5D2BrqIuDCeD/9dZVnYETX7qrrKvZZ8qnH8KJh+Q/MVhGW68GBlOb+4aGSQKBgQCtDlkH9o7kScYK54PqBfaBNktDExXxFURrgcbQavKVhfQBvmx7gWBVu2E6qw1toYPvnxCLOYM2AyrDGtxrledWHMqg2S/osemNKy9S0d1LaTn7mx4UU6jNvc0YSQLO5eEgrfWin0DK4V2XTQ2tcCJALBX+6ohkGd6I8v9qDv6UpQKBgHu8EQz8uaQbV6iOhs+333Sv1quUnjyLK7s5ncC27DO15n42Bklm+qQDrtGYTotXV5bKt6/myn8Z9vnhkVUhuP/j70djtH5o+0O/VeIeX/NoUr5Pwb1hcG1rcn3gdz+a6YRX84pGPuPVWwiX8oEEZqrPQf5tQ+NpWq+Dgxbk9u7BAoGBAKuy45SqBSXtKEfjYhdL6UJUGyImiouSnTrJHPfmVF8T83/TbhCv0WPeMN6jngQazytst15bJprtU30WZoF7znW6xTQHqKgH9QAyS5axUA2lDnlbcuGaC45t9VJWBio/HDlvP5okxYVFSrV2Js7gxarD0uMD0WT34bog1ldlHyNdAoGBAI5dAabdvMMHCNm3tSoHQweeK21w3EvVLHuzA328p2DVzO3hX32mGet8vO2bB5xxOuh/1qS8feAaOUahRBTEm5ALtpNawFWLfMlQeO6BWdHiisogJQjHqIyIt3qJbARZa8j7Con35ETiHYS6M2WTmMh/+PLrNILBeyWb0Gj9Lx51',
        'log' => [ // optional
            'file' => './logs/alipay.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [ // optional
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
        ],
        'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
    ];

    public function recharge(){
        $user = $this->auth->getUserinfo();
        $params = $this->request->param();
        $order = [
            'out_trade_no' =>time(),
            'total_amount' => $params['price'],
            'subject' => '盲盒充值',
        ];

        $alipay = Pay::alipay($this->config)->app($order);
        $order['userid'] = $user['id'];
        $order['createtime'] = time();
        $order['type'] = 'alipay';
        $res = Db::name('recharge')->insertGetId($order);

        return $alipay->send();


    }



    // 回调地址
    public function notify(){
        //日志路径
        $path = "/www/wwwroot/mhe.yxbnet.cn/public/log/rechargedata/" . date("Ymd");
        //判断是否有这个路径
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $paytype = $this->request->param('paytype');
        $pay = \addons\epay\library\Service::checkNotify($paytype);
        if (!$pay) {
            echo '签名错误';
            return;
        }
        $data = $pay->verify();
        try {
            if($paytype=='alipay' && $data['trade_status']=='TRADE_SUCCESS'){
                $pay_type = 0;
                $pay_time = strtotime($data['gmt_payment']);
            }elseif($data['return_code']=='SUCCESS' && $data['result_code']=='SUCCESS'){
                $pay_type = 1;
                $pay_time = strtotime($data['time_end']);
            }
            // 你可以在此编写订单逻辑
            $order_no = $data['out_trade_no'];
            $info = Db::name('recharge')->where('out_trade_no', $order_no)->find();
            $info['pay_type'] = $pay_type;
            $info['pay_time'] = $pay_time;
            $res = Db::name('recharge')->where('out_trade_no', $order_no)->update($info);
            if ($res){
                Db::name('user')->where('id',$info['userid'])->setInc('money',$info['price']);
            }
            file_put_contents($path . "/" . '-' . time() . ".log", '保存成功-' .'success');

            echo "success";
            return;
        } catch (Exception $e) {

        }
        echo $pay->success();
    }
//充值记录
    public function apijilu(){
        $user = $this->auth->getUser();

        $data = Db::name('recharge')
            ->where('userid',$user['id'])
            ->field('total_amount,pay_type,pay_time')
            ->where('pay_type',1)
            ->select();
        if (empty($data)) $this->success('success',$data);
        if ($data){
            foreach ($data as $k=>$v){
                $data[$k]['pay_time'] =date('Y-m-d H:i:s',$v['pay_time']);
            }
        }
        $this->success('success',$data);
    }
}