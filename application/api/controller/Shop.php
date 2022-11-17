<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 示例接口
 */
class Shop extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['shopcate', 'commodity','commDetails','shopsearch','Delivery','logisticslist','deshop','buyshop'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];
    //上次分类
    public function shopcate(){

        $data  = Db::name('shopcate')->order('id desc')->select();

            $this->success('SUCCESS',$data);
    }
    //商城商品
    public function commodity(){

        $c_id = $this->request->param('c_id');
        $shai = $this->request->param('shai');
        switch ($shai){
            case 0:
                if (empty($c_id)){
                    $data = Db::name('commodity')
                        ->field('id,title,brand,images')
                        ->select();
                }else{
                    $data = Db::name('commodity')
                        ->field('id,title,brand,images')
                        ->where('c_id',$c_id)
                        ->select();
                }
                $p=[];
                foreach ($data as $k=>$v){
                    $price = Db::name('speci')
                        ->where('s_id',$v['id'])
                        ->field('price')
                        ->select();
                    $arrayh  = array_column($price, 'price');
                    $data[$k]['price'] = min($arrayh) ."--".max($arrayh);
                }
            break;
            case 1:
                if (empty($c_id)){
                    $data = Db::name('commodity')->order('id desc')
                        ->field('id,title,brand,images')
                        ->select();
                }else{
                    $data = Db::name('commodity')
                        ->order('id desc')
                        ->field('id,title,brand,images')
                        ->where('c_id',$c_id)
                        ->select();
                }
                foreach ($data as $k=>$v){
                    $price = Db::name('speci')
                        ->where('s_id',$v['id'])
                        ->field('price')
                        ->select();
                    $arrayh  = array_column($price, 'price');
                    $data[$k]['price'] = min($arrayh) ."--".max($arrayh);
                }
                break;
            case 2:
                if (empty($c_id)){
                    $data = Db::name('commodity')->order('id asc')
                        ->field('id,title,brand,images')
                        ->select();
                }else{
                    $data = Db::name('commodity')
                        ->order('id asc')
                        ->field('id,title,brand,images')
                        ->where('c_id',$c_id)
                        ->select();
                }
                foreach ($data as $k=>$v){
                    $price = Db::name('speci')
                        ->where('s_id',$v['id'])
                        ->field('price')
                        ->select();
                    $arrayh  = array_column($price, 'price');
                    $data[$k]['price'] = min($arrayh) ."--".max($arrayh);
                }
                break;


        }

        $this->success('success',$data);
    }
//商品详情
    public function commDetails(){
        $id = $this->request->param('g_id');

        $data = Db::name('commodity')
                ->where('id',$id)
                ->find();

        $price = Db::name('speci')
            ->where('s_id',$id)
            ->field('id,specifi,price')
            ->select();
        foreach ($price as $k =>$v){
            $price[$k]['p_id'] = $v['id'];

//            unset($v,$price[$k]['id']);
            $price[$k]['data'] = $data;
            $price[$k]['data']['price'] = $v['price'];

        }

        $this->success('success',$price);
    }
    public function delivergoods(){

        $user = $this->auth->getUser();
        $data = $this->request->param();

        $status = Db::name('user')->where('id',$user['id'])->value('identity');

        $res =[
          'status'=>''
        ];

    }
//商城搜索
    public function shopsearch(){
        $user = $this->auth->getUser();
        $search = $this->request->param('search');
        $data = Db::name('commodity')
            ->where('title','like','%'.$search.'%')
            ->order('id esc')
            ->select();
        foreach ($data as $k=>$v){
            $data[$k]['images'] = $_SERVER['HTTP_HOST'].$v['images'];
            $price = Db::name('speci')
                ->where('s_id',$v['id'])
                ->field('price')
                ->select();
            $arrayh  = array_column($price, 'price');
            $data[$k]['price'] = min($arrayh) ."--".max($arrayh);

        }
        if ($data){
            $das = Db::name('search')->where('name',$search)->find();
            if (empty($das)){
                $arr=[
                    'userid'=> $user['id'],
                    'name'  => $search,
                    'status'  => 2,
                    'createtime'=>time()
                ];
                Db::name('search')->insert($arr);
            }else{
                Db::name('search')->where('name',$search)->setInc('number',1);
            }
        }
        $this->success('success',$data);
    }

    //配送页商品详情
    public function deshop(){
        $id = $this->request->param('id');

        $status = Db::name('getarard')->where('id',$id)->value('status');
        if ($status ==1){
            $data = Db::name('getarard')
                ->alias('a')
                ->join('commodity b','a.m_id = b.id')
                ->field('a.id,b.id as m_id,b.images,b.title,a.price,a.actual,a.status,a.type,a.createtime')
                ->where('a.id',$id)
                ->find();
        }else{
            $data = Db::name('getarard')
                ->alias('a')
                ->join('bshop b','a.m_id = b.id')
                ->field('a.id,b.id as m_id,b.s_image,b.s_name,a.price,a.actual,a.createtime')
                ->where('a.id',$id)
                ->find();
        }


        $data['createtime'] = date('Y-m-d H:i',$data['createtime']);
        $this->success('success',$data);
    }

//点击配送
    public function Delivery(){
        $data = $this->request->param();
        $userid = $this->auth->getUser();


        $price= Db::name('recharge')->where('userid',$userid['id'])->where('pay_type',1)->sum('total_amount');
        if ($price< 35) $this->error('首冲未达到35，无法配送');
        $ide = Db::name('user')->where('id',$userid['id'])->value('identity');

        if (empty($data['k_id'])) $this->error('k_id不能为空');
        if (empty($data['ReceiverName'])) $this->error('收货人不能为空');
        if (empty($data['ReceiverTel'])) $this->error('手机号码不能为空');
        if (empty($data['ReceiverProvinceName'])) $this->error('详细地址不能为空');
        $arr = [
            'status' => $ide,
            'userid' => $userid['id'],
            'm_id'   => $data['m_id'],
            'k_id'   => $data['k_id'],
            'source' => $data['source'],
            'ReceiverName' => $data['ReceiverName'],
            'ReceiverTel'  => $data['ReceiverTel'],
            'ReceiverProvinceName' => $data['ReceiverProvinceName'],
            'OrderCode' => $this->OrderCode(),
            'createtime' => time(),
        ];

        $res = Db::name('order')->insert($arr);
        if ($res){
            Db::name('getarard')->where('id',$data['k_id'])->update(['type'=>2]);
            $this->success('配送成功');
        }else{
            $this->error('系统异常,稍后重试');
        }
    }



    function OrderCode(){
        $osn = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

        return $osn;
    }

    public function logisticslist(){
        $user =$this->auth->getUser();
        $type = $this->request->param('type');

        switch ($type){
            case 1:
                $data = Db::name('order')
                    ->where('userid',$user['id'])
                    ->order('createtime desc')
                    ->select();
                $data2=[];
                foreach ($data as $k=>$v){
                    if ($v['source'] == 1){

                        $shop = Db::name('bshop')->where('id',$v['m_id'])->find();
                        $kucun = Db::name('getarard')->where('id',$v['k_id'])->find();
                        $data1['image'] =$shop['s_image'];
                        $data1['title'] =$shop['s_name'];
                        $data1['price'] =$kucun['price'];
                        $data1['actual'] =$kucun['actual'];
                        $data1['OrderCode'] =$v['OrderCode'];
                        $data1['courierNumber'] =$v['courierNumber'];
                        $data1['express'] =$v['express'];
                        $data1['expressName'] =Db::name('kuaicom')->where('id',$v['express'])->value('company');
                        $data1['createtime'] =$v['createtime'];
                        $data1['type'] =$v['type'];
                        array_push($data2,$data1);
                    }else{
                        $shop1 = Db::name('commodity')->where('id',$v['m_id'])->find();
//                        print_r($shop1);die;
                        $kucun = Db::name('getarard')->where('id',$v['k_id'])->find();

                        $data1['image'] =$shop1['images'];
                        $data1['title'] =$shop1['title'];
                        $data1['price'] =$kucun['price'];
                        $data1['actual'] =$kucun['actual'];
                        $data1['OrderCode'] =$v['OrderCode'];
                        $data1['courierNumber'] =$v['courierNumber'];
                        $data1['express'] =$v['express'];
                        $data1['expressName'] =Db::name('kuaicom')->where('id',$v['express'])->value('company');

                        $data1['createtime'] =$v['createtime'];
                        $data1['type'] =$v['type'];
                        array_push($data2,$data1);
                    }
                }

                foreach ($data2 as $k=>$v){
                    $data2[$k]['creayetime'] = date('Y-m-d H:i:s',$v['createtime']);
                }
                $this->success('success',$data2);

                break;

            case 2:
                $data = Db::name('order')
                    ->where('userid',$user['id'])
                    ->order('createtime desc')
                    ->where('type',1)
                    ->select();

                $data2=[];
                foreach ($data as $k=>$v){
                    if ($v['source'] == 1){

                        $shop = Db::name('bshop')->where('id',$v['m_id'])->find();
                        $kucun = Db::name('getarard')->where('id',$v['k_id'])->find();
                        $data1['image'] =$shop['s_image'];
                        $data1['title'] =$shop['s_name'];
                        $data1['price'] =$kucun['price'];
                        $data1['actual'] =$kucun['actual'];
                        $data1['OrderCode'] =$v['OrderCode'];
                        $data1['courierNumber'] =$v['courierNumber'];
                        $data1['express'] =$v['express'];
                        $data1['createtime'] =$v['createtime'];
                        $data1['type'] =$v['type'];
                        array_push($data2,$data1);
                    }else{
                        $shop1 = Db::name('commodity')->where('id',$v['m_id'])->find();
                        $kucun = Db::name('getarard')->where('id',$v['k_id'])->find();
                        $data1['image'] =$shop1['images'];
                        $data1['title'] =$shop1['title'];
                        $data1['price'] =$kucun['price'];
                        $data1['actual'] =$kucun['actual'];
                        $data1['OrderCode'] =$v['OrderCode'];
                        $data1['courierNumber'] =$v['courierNumber'];
                        $data1['express'] =$v['express'];
                        $data1['createtime'] =$v['createtime'];
                        $data1['type'] =$v['type'];
                        array_push($data2,$data1);
                    }
                }
                foreach ($data2 as $k=>$v){
                    $data2[$k]['creayetime'] = date('Y-m-d H:i:s',$v['createtime']);
                }
                $this->success('success',$data2);

                break;
            case 3:
                $data = Db::name('order')
                    ->where('userid',$user['id'])
                    ->order('createtime desc')
                    ->where('type',2)
                    ->select();

                $data2=[];
                foreach ($data as $k=>$v){
                    if ($v['source'] == 1){

                        $shop = Db::name('bshop')->where('id',$v['m_id'])->find();
                        $kucun = Db::name('getarard')->where('id',$v['k_id'])->find();
                        $data1['image'] =$shop['s_image'];
                        $data1['title'] =$shop['s_name'];
                        $data1['price'] =$kucun['price'];
                        $data1['actual'] =$kucun['actual'];
                        $data1['OrderCode'] =$v['OrderCode'];
                        $data1['courierNumber'] =$v['courierNumber'];
                        $data1['express'] =$v['express'];
                        $data1['createtime'] =$v['createtime'];
                        $data1['type'] =$v['type'];
                        array_push($data2,$data1);
                    }else{
                        $shop1 = Db::name('commodity')->where('id',$v['m_id'])->find();
                        $kucun = Db::name('getarard')->where('id',$v['k_id'])->find();
                        $data1['image'] =$shop1['images'];
                        $data1['title'] =$shop1['title'];
                        $data1['price'] =$kucun['price'];
                        $data1['actual'] =$kucun['actual'];
                        $data1['OrderCode'] =$v['OrderCode'];
                        $data1['courierNumber'] =$v['courierNumber'];
                        $data1['express'] =$v['express'];
                        $data1['createtime'] =$v['createtime'];
                        $data1['type'] =$v['type'];
                        array_push($data2,$data1);
                    }
                }
                foreach ($data2 as $k=>$v){
                    $data2[$k]['creayetime'] = date('Y-m-d H:i:s',$v['createtime']);
                }
                $this->success('success',$data2);

                break;
        }
    }


    public function logistics(){
        $p_id = $this->request->param();
    }



    public function buyshop(){

        $user = $this->auth->getUser();
        $m_id = $this->request->param('m_id');
        $g_id = $this->request->param('g_id');

        $data = [
          'userid' => $user['id'],
          'm_id'   => $m_id,
          'g_id'   => $m_id,
          'status' =>1,
          'price' =>Db::name('speci')->where('id',$g_id)->value('price'),
          'actual' =>Db::name('speci')->where('id',$g_id)->value('price'),
          'createtime' =>time(),

        ];
//        print_r($data);die;
        $res = Db::name('getarard')->insert($data);
        if ($res){
            $this->success('购买成功');
        }else{
            $this->error('购买失败');
        }

    }
}
