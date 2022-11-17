<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['useCode','record','index','xiang','rule','zhuxiao'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $user = $this->auth->getUser();
        $data = Db::name('user')
               ->where('id',$user['id'])
                ->field('username,money')
                ->find();
        $data['xiang'] =Db::name('mang')->where('userid',$user['id'])->count();
        $data['toux'] =Config::get('site.toux');

        $this->success('success',$data);
    }



    /**
     * 使用充值码
     * @return string
     */
    public function useCode() {
        $data = $this->request->param();
        $user = $this->auth->getUser();
//        $user['id'] = 1;
        $ress = Db::name('rechargecode')
              ->where('recharge',$data['recharge'])
              ->where('userid', $user['id'])
              ->find();
        if (empty($ress)) {
            $this->error('充值码不存在');
        }else{
            if ($ress['status']==1) $this->error('充值码已使用');
            $res = Db::name('rechargecode')
                ->where('recharge',$data['recharge'])
                ->update(['status'=>1]);
            if ($res){
                $set =Db::name('user')
                    ->where('id',$user['id'])
                    ->setInc('money',$ress['money']);
                if ($set){
                    $this->success('使用成功');
                }
            }else{
                $this->error('系统错误 ');
            }
        }


    }

//首页轮播
    public function record(){
        $data = Db::name('record')
                ->order('createtime desc')
                ->select();

        foreach($data as $k =>$v){
            $data[$k]['image'] = 'https://'. $_SERVER['HTTP_HOST'].$v['image'];
        }
        $this->success('success',$data);
    }


    public function xiang(){
        $user = $this->auth->getUser();

        $mang =Db::name('mang')->where('userid',$user['id'])->select();
        if(empty($mang)) $this->success('success',$mang);

        foreach ($mang as $k=>$v){
            $data[]= Db::name('bulid')
                ->where('id',$v['m_id'])
                ->find();
        }
        foreach ($data as $k=>$v){
            $data[$k]['type'] = 2;
            $data[$k]['sp'] = Db::name('bshop')
                ->where('c_id',$v['id'])
                ->field('s_image')
                ->select();
        }
        $this->success('success',$data);
    }



    public function rule(){
        $this->success('succsee',Config::get('site.dengji'));
    }

//注销账户

    public function zhuxiao(){
        $user = $this->auth->getUser();
        $res = Db::name('user')->where('id',$user['id'])->delete();
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        if ($res){
            $this->success('注销成功');
        }else{
            $this->error('注销失败');
        }
    }
}
