<?php
namespace app\index\controller;
use think\db;
use think\Controller;
class Type extends Controller
{
    /**
     * @return 展示分类添加页面
     */
    public function type_add()
    {
        return view('type_add');
    }

    /**
     * @return 分类列表添加
     */
    public function type_list()
    {
        $data = Db::table('type')->where('is_del',0)->select();
        return view('type_list',['data' => $data]);
    }

    /**
     * 执行分类添加方法
     */
    public function type_add_do()
    {
        $data = input('post.');
        $res = Db::table('type')->insert($data);
        if($res){
            echo "<script>alert('商品分类添加成功');</script>";
            echo "<script>location.href='index.php?s=index/type/type_list';</script>";
        }else{
            echo "<script>alert('商品分类添加失败');</script>";
            echo "<script>location.href='index.php?s=index/type/type_add';</script>";
        }
    }

    /**
     * 分类删除
     */
    public function type_del()
    {
        $type_id = input('get.type_id');
        $res = Db::table('type')
            ->where('type_id', $type_id)
            ->update(['is_del' => 1]);
        if($res){
            echo "<script>alert('商品分类删除成功');</script>";
            echo "<script>location.href='index.php?s=index/type/type_list';</script>";
        }else{
            echo "<script>alert('商品分类删除失败');</script>";
            echo "<script>location.href='index.php?s=index/type/type_list';</script>";
        }
    }
}
