<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;

/**
 * 示例接口
 */
class Extension extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['giftBag', 'updateCode','explain','extension'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];


    public function giftBag(){
//        $user = $this->auth->getUser();
        $code = $this->request->param('code');
        $username = $this->request->param('username');
        $userdata  = Db::name('user')->where('username',$username)->find();
//        print_r($user['id']);die;
        if ($userdata['identity'] ==1){

            $p_id = Db::name('user')
                ->where('extension',$code)
                ->field('id,admin_id')
                ->find();
            if (empty($p_id)) $this->error('礼包码不存在');

            $p = Db::name('user')
                ->where('id',$userdata['id'])
                ->value('p_id');

            if ($p) $this->error('只能使用一个礼包码');

            $res = Db::name('user')
                ->where('id',$userdata['id'])
                ->update(['p_id'=>$p_id['id'],'admin_id'=>$p_id['admin_id']]);
        }else{
            $admin_id = Db::name('admin')
                ->where('admcodein',$code)
                ->value('id');
            if (empty($p_id)) $this->error('礼包码不存在');
            $p = Db::name('user')
                ->where('id',$userdata['id'])
                ->value('p_id');

            if ($p) $this->error('只能使用一个礼包码');

            $res = Db::name('user')
                ->where('id',$userdata['id'])
                ->update(['admin_id'=>$admin_id]);
        }

        if ($res){
            $this->success('礼包码使用成功');
        }else{
            $this->error('系统异常');
        }
    }
    //修改推广码
    public function updateCode(){
        $user = $this->auth->getUser();
        $code = $this->request->param('code');
        $res = preg_match("/^[a-zA-Z-0-9]{6,8}$/",$code);
        if ($res){
            $res = Db::name('user')->where('extension',$code)->find();
            if ($res) $this->error('修改失败,换一个试试');
            $art = Db::name('user')->where('id',$user['id'])->update(['extension'=>$code]);
            if ($art){
                $this->success('修改成功');
            }
        }else{
            $this->error('推广码必须6-8位');
        }

    }


//说明
    public function explain(){
        $type = $this->request->param('type');
        switch ($type){
            //开箱规则
            case 1:
                $data = Config::get('site.Unpacking');
                $this->success('success',$data);
            break;
            //常见问题
            case 2:
                $data = Config::get('site.problem');
                $this->success('success',$data);
            break;
            //隐私政策
            case 3:
                $data = Config::get('site.Privacy');
                $this->success('success',$data);
                break;
            //购买指南
            case 4:
                $data = Config::get('site.buy');
                $this->success('success',$data);
                break;
            //配送温馨提示
            case 5:
                $data = Config::get('site.Delivery');
                $this->success('success',$data);
                break;
            //充值说明
            case 6:
                $data = Config::get('site.Recharge');
                $this->success('success',$data);
                break;
            //用户协议
            case 7:
                $data = Config::get('site.xieyi');
                $this->success('success',$data);
                break;
        }
    }

//我的推广
    public function extension(){
        $user = $this->auth->getUser();
        $money = Db::name('extension')->where('userid',$user['id'])->sum('money');
        $level = Db::name('user')->where('id',$user['id'])->value('level');
        $tuiguang = Db::name('user')->where('id',$user['id'])->value('extension');
        $geade = Db::name('grade')->where('name',$level)->value('commissionrate');
        $tgju = Db::name('extension')->where('userid',$user['id'])->order('createtime')->field('createtime,money,status')->select();
        foreach ($tgju as $k=>$v){
            $tgju[$k]['createtime'] = date('Y-m-d',$v['createtime']);
        }
        $data = [
            'money' =>$money,
            'level' =>$level,
            'tuiguang' =>$tuiguang,
            'geade' =>$geade,
            'tgju' =>$tgju,

        ];
        $this->success('success',$data);
    }
}