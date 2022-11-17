<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 示例接口
 */
class Mang extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['mang', 'pageCare','homePage','mangdelite','bshopdetaile','recovery','pirecovery','search','searchList','searchHot','dellist'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;  //获得奖品的ID
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }
//点击抽奖
    public function mang(){
        $user = $this->auth->getUser();
//        $user['id'] = 1;
        $c_id =$this->request->param('c_id');
        $cishu = $this->request->param('cishu');
        $type  = $this->request->param('type');
        $l_id =$this->request->param('l_id');
        $identity = Db::name('user')->where('id',$user['id'])->value('identity');
        $money = Db::name('user')->where('id',$user['id'])->value('money');
        $prize = Db::name('bulid')->where('id',$c_id)->value('price');
        $zongprice = $cishu *$prize ;
        if ($type == 1){
            if ($zongprice > $money) $this->error('余额不足，先充值余额');
            $del = Db::name('user')->where('id',$user['id'])->setDec('money',$zongprice);
            Db::name('bulid')->where('id',$c_id)->setInc('frequency',$cishu);
        }

        if ($identity == 2){
            $prize_arr = Db::name('bshop')->where('c_id',$c_id)->select();
            foreach ($prize_arr as $key => $val)
            {
                $arr[$val['id']] = $val['probability'];  //这里$arr的‘id’是从1开始的哦。
            }

            $aty =[];
            for ($i = 0; $i < 100; $i++) {

                if (($i + 1)<=$cishu) {
                    $rid = $this->get_rand($arr);
                    array_push($aty,$rid);
                }
            }
            $res=[];

            foreach ($aty as $k =>$v){
                $re = Db::name('bshop')
                    ->where('id',$v)
                    ->field('id,s_name,s_image,title,price,probability')
                    ->find(); //中奖项
                array_push($res,$re);
            }
        }else{
            $prize_arr = Db::name('anchor')->where('m_id',$c_id)->select();
            foreach ($prize_arr as $key => $val)
            {
                $arr[$val['id']] = $val['probability'];  //这里$arr的‘id’是从1开始的哦。
            }
            $aty =[];
            for ($i = 0; $i < 100; $i++) {

                if (($i + 1)<=$cishu) {
                    $rid = $this->get_rand($arr);
                    array_push($aty,$rid);
                }
            }
            $id = [];


            $ress=[];
            foreach ($aty as $k =>$v){
                $re = Db::name('anchor')
                    ->where('id',$v)
                    ->field('id,s_id')
                    ->find(); //中奖项
                array_push($ress,$re);
            }

            $res=[];
            foreach ($ress as $k =>$v){
                $re = Db::name('bshop')
                    ->where('id',$v['s_id'])
                    ->field('id,s_name,s_image,title,price,probability')
                    ->find(); //中奖项
                array_push($res,$re);
            }
        }


        $data =[
            'userid' => $user['id'],
            'status' => '2',
            'actual' => $prize,
            'createtime'=>time()
        ];

        $arry = [];
        if ($type == 2){
            foreach ($res as $K => $v){
                $data['m_id']=$v['id'];
                $data['price']=0;
                $getid =  Db::name('getarard')->insertGetId($data);
                array_push($arry,$getid);
            }
            Db::name('mang')->where('id',$l_id)->update(['type'=>2]);
        }else{

//            echo 1111;die;
            $p_id = Db::name('user')->where('id',$user['id'])->value('p_id');
            $grade   = Db::name('user')->where('id',$p_id)->value('level');
            $commissionrate = Db::name('grade')->where('name',$grade)->value('commissionrate');

            $arr = [
                'userid' => $p_id,
                'money'  => $zongprice * $commissionrate /100,
                'createtime'=>time()
            ];

            $ress = Db::name('extension')->insert($arr);
                if ($res){
                    $money = Db::name('extension')->where('userid',$p_id)->sum('money');
                    $xiaji = $grade+1;
                    $condition = Db::name('grade')->where('name',$grade)->value('condition');
                    if ($money>= $condition){
                        Db::name('user')->where('id',$p_id)->update(['level'=>$xiaji]);
                    }
                }
            foreach ($res as $K => $v){
                $data['m_id']=$v['id'];
                $data['price']=$v['price'];
                $getid =  Db::name('getarard')->insertGetId($data);
                array_push($arry,$getid);
            }
        }


        $shuju =[];
        foreach ($arry as $k=>$v){
            $da = Db::name('getarard')
                ->alias('a')
                ->join('bshop b','a.m_id = b.id')
                ->field('a.id as a_id,b.id,b.s_name,b.s_image,b.title,b.price,b.probability')
                ->where('a.id',$v)
                ->find();
            array_push($shuju,$da);
        }

        $zongjia=[];
        foreach ($shuju as $k=>$v){
            array_push($zongjia,$v['price']);
        }
        foreach ($shuju as $k => $v){
            $shuju[$k]['type'] = '回收';
            $shuju[$k]['showStyle'] = 'show_a';
            $shuju[$k]['recycling'] = 'recycling_a';
        }
        $shuju1['zongjia'] = array_sum($zongjia);
        $shuju1['data'] = $shuju;

        $this->success('success',$shuju1);
    }

    /**
     * 首页开箱分类
     */
    public function pageCare(){
        $data = Db::name('caser')->order('id esc')->select();
        $this->success('success',$data);
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 盲盒数据
     */
    public function homePage(){
    $c_id =$this->request->param('c_id');
    if (empty($c_id)){
        $data = Db::name('bulid')->order('id esc')->select();
    }else{
        $data = Db::name('bulid')
                ->where('c_id',$c_id)
                ->order('id esc')
                ->select();
    }
    foreach ($data as $k=>$v){
        $data[$k]['type'] = 1;

        $data[$k]['sp'] = Db::name('bshop')
                    ->where('c_id',$v['id'])
                    ->field('s_image')
                    ->select();

    }

    $this->success('success',$data);
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 盲盒详情
     */
    public function  mangdelite(){
        $id = $this->request->param('c_id');
        $data = Db::name('bshop')
            ->where('c_id',$id)
            ->select();

        $price = Db::name('bulid')
            ->where('id',$id)
            ->value('price');
        $frequency = Db::name('bulid')
            ->where('id',$id)
            ->value('frequency');


        $pr = [
            $price*1,
            $price*2,
            $price*3,
            $price*4,
            $price*5,
        ];
        $res['price'] = $pr;
        $res['data'] = $data;
        $res['frequency'] = $frequency;
        $this->success('success',$res);
    }

    public function bshopdetaile(){
        $id = $this->request->param('id');
        $data = Db::name('bshop')
                ->where('id',$id)
                ->find();
        $this->success('success',$data);
    }
    //回收
    public function recovery(){
        $user = $this->auth->getUser();
//        $user['id'] =1;
        $g_id = $this->request->param('g_id');
        $res = Db::name('getarard')->where('id',$g_id)->update(['type'=>3]);

        if ($res){
            $price = Db::name('getarard')->where('id',$g_id)->value('price');

            $arr = Db::name('user')->where('id',$user['id'])->setInc('money', $price);

            if ($arr){
                $this->success('回收成功');
            }else{
                $this->error('回收失败');
            }
        }else{
            $this->error('库存不存在');
        }


    }
//批量回收
    public function pirecovery(){
                $user = $this->auth->getUser();
        $g_id = $this->request->param('g_id');
        $g_id = explode(',',$g_id);
//        print_r($g_id);die;
        foreach ($g_id as $v) {
            $res = Db::name('getarard')->where('id',$v)->update(['type'=>3]);

            if ($res){
                $price = Db::name('getarard')->where('id',$v)->value('price');
                $arr = Db::name('user')->where('id',$user['id'])->setInc('money', $price);
            }
        }

        if ($arr){
            $this->success('回收成功');
        }else{
            $this->error('回收失败');
        }

    }

//盲盒搜索
    public function search(){
        $user = $this->auth->getUser();
        $search = $this->request->param('search');
        $data = Db::name('bulid')
            ->where('b_name','like','%'.$search.'%')
            ->order('id esc')
            ->select();
        foreach ($data as $k=>$v){
            $data[$k]['b_image'] = $_SERVER['HTTP_HOST'].$v['b_image'];
            $data[$k]['sp'] = Db::name('bshop')
                ->where('c_id',$v['id'])
                ->field('s_image')
                ->select();
        }

        if ($data){
            $das = Db::name('search')->where('name',$search)->find();
            if (empty($das)){
                $arr=[
                    'userid'=> $user['id'],
                    'name'  => $search,
                    'status'=> 1,
                    'createtime'=>time()
                ];
                Db::name('search')->insert($arr);
            }else{
                Db::name('search')->where('name',$search)->setInc('number',1);
            }
        }
        $this->success('success',$data);
    }

//盲盒搜索记录
    public function searchList(){
        $user = $this->auth->getUser();
        $type = $this->request->param('type');
        $mySea = Db::name('search')
            ->where('userid',$user['id'])
            ->order('createtime desc')
            ->where('status',$type)
            ->where('type',1)
            ->limit(10)
            ->field('name')
            ->select();
        $this->success('success',$mySea);
    }
//热门搜索记录
    public function searchHot(){
        $type = $this->request->param('type');
        $mySea = Db::name('search')
            ->order('number desc')
            ->where('status',$type)
            ->limit(10)
            ->field('name')
            ->select();
        $this->success('success',$mySea);
    }

    public function dellist(){
        $user = $this->auth->getUser();
        $type = $this->request->param('type');

        $res = Db::name('search')->where('userid',$user['id'])->update(['type'=>2]);
        if ($res){
            $this->success('删除成功');
        }else{
            $this->error('系统异常,删除失败');
        }
    }
}