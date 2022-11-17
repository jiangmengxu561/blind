<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 示例接口
 */
class Stock extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['stockdata', 'give'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /*
    $array:需要排序的数组
    $keys:需要根据某个key排序
    $sort:倒叙还是顺序
    */
    function arraySort($array,$keys,$sort='asc') {
        $newArr = $valArr = array();
        foreach ($array as $key=>$value) {
            $valArr[$key] = $value[$keys];
        }
        ($sort == 'asc') ?  asort($valArr) : arsort($valArr);//先利用keys对数组排序，目的是把目标数组的key排好序
        reset($valArr); //指针指向数组第一个值
        foreach($valArr as $key=>$value) {
            $newArr[$key] =$array[$key];
//            $newArr[$key]['args'] = array_values($value['args']);
        }
        return $newArr;
    }

    public  function  stockdata(){
        $user = $this->auth->getUser();
        $c_id = $this->request->param('c_id');

            switch ($c_id){
                case 0 :
                    $data = [];
                    $goumai = Db::name('getarard')
                        ->alias('a')
                        ->join('commodity b','a.m_id = b.id')
                        ->field('a.id,b.id as m_id,b.images,b.title,a.price,a.actual,a.status,a.type,a.createtime')
                        ->where('a.userid',$user['id'])
//                        ->where('a.status',1)
                        ->select();
                    foreach ($goumai as $k=>$v){
                        $goumai[$k]['s_image'] = $v['images'];
                        unset($goumai[$k]['images']);
                    }

                    if (!empty($goumai)){
                        foreach ($goumai as $k=>$v){
                            array_push($data,$v);
                        }
                    }
                    $mang = Db::name('getarard')
                        ->alias('a')
                        ->join('bshop b','a.m_id = b.id')
                        ->field('a.id,b.id as m_id,b.s_image,b.title ,a.price,a.actual,a.status,a.type,a.createtime')
                        ->where('a.userid',$user['id'])
                        ->where('a.status',2)
                        ->select();

                    if (!empty($mang)){
                        foreach ($mang as $k=>$v){
                            array_push($data,$v);
                        }
                    }
                    break;
                case 1 :

                    $data = Db::name('getarard')
                        ->alias('a')
                        ->join('commodity b','a.m_id = b.id')
                        ->field('a.id,b.id as m_id,b.images,b.title,a.price,a.actual,a.status,a.type,a.createtime')
                        ->where('a.userid',$user['id'])
                        ->where('a.status',1)
                        ->select();
                    foreach ($data as $k=>$v){
                        $data[$k]['s_image'] = $v['images'];
                       unset($data[$k]['images']);
                    }
//                    print_r($data);die;
                    break;
                case 2 :
                    $data = Db::name('getarard')
                        ->alias('a')
                        ->join('bshop b','a.m_id = b.id')
                        ->field('a.id,b.id as m_id,b.s_image,b.title ,a.price,a.actual,a.status,a.type,a.createtime')
                        ->where('a.userid',$user['id'])
                        ->where('a.status',2)
                        ->select();
                    break;
                case 3:
                    $data = [];
                    $goumai = Db::name('getarard')
                        ->alias('a')
                        ->join('commodity b','a.m_id = b.id')
                        ->field('a.id,b.id as m_id,b.images,b.title,a.price,a.actual,a.status,a.type,a.createtime')
                        ->where('a.userid',$user['id'])
//                        ->where('a.status',1)
                        ->where('a.type',1)
                        ->select();

                    if (!empty($goumai)){
                        foreach ($goumai as $k=>$v){
                            array_push($data,$v);
                        }
                    }
                    $mang = Db::name('getarard')
                        ->alias('a')
                        ->join('bshop b','a.m_id = b.id')
                        ->field('a.id,b.id as m_id,b.s_image,b.title ,a.price,a.actual,a.status,a.type,a.createtime')
                        ->where('a.userid',$user['id'])
//                        ->where('a.status',2)
                        ->where('a.type',1)
                        ->select();
                    if (!empty($mang)){
                        foreach ($mang as $k=>$v){
                            array_push($data,$v);
                        }
                    }
                    break;
            }


            foreach ($data as $k=>$v){
                $data[$k]['createtime'] = date('Y-m-d H:i');
            }

       $data1 = $this->arraySort($data,'type');

        $arr = [
          'code' => 1,
          'msg'  =>'success',
          'data' => array_values($data1)
        ];

        return json_encode($arr,true);
    }

    //赠送库存
    public function give(){

        $username = $this->request->param('username');
        $id = $this->request->param('id');
        $userid = Db::name('user')->where('username',$username)->field('id')->find();
        if (empty($userid['id'])) $this->error('暂无此用户');
        $res = Db::name('getarard')->where('id',$id)->update(['userid'=>$userid['id']]);
        if ($res){
            $this->success('赠送成功');
        }else{
            $this->error('系统异常,请稍后重试');
        }
    }
}
