<?php

namespace app\admin\model;

use think\Model;


class Commodity extends Model
{

    

    

    // 表名
    protected $name = 'commodity';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function shopcate()
    {
        return $this->belongsTo('Shopcate', 'c_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
