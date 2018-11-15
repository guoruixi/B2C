<?php
namespace app\index\controller;
use think\db;
use think\Controller;
use think\image;
class Category extends Controller
{

    /**
     * @return 展示商品分类添加页面
     */
    public function category_add()
    {
        $data = Db::table('category')->select();
        return view('category_add',['data' => $data]);
    }

    /**
     * @return 商品分类展示
     */
    public function category_list()
    {
        $data = Db::table('category')
                    ->alias('a')
                    ->join('image b','a.topic_img_id = b.id')
                    ->select();
        return view('category_list',['data' => $data]);
    }

    /**
     * 执行商品类型添加的方法
     */
    public function category_add_do()
    {
        $data = input('post.');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('img');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move("uploads/");
        if($info){
            $img_data['url'] = 'uploads/'.$info->getSaveName();
            Db::table('image')->insert($img_data);

            $data['topic_img_id'] = Db::table('image')->getLastInsID();
            $res = Db::table('category')->insert($data);
            if($res){
                echo "<script>alert('商品分类添加成功');</script>";
                echo "<script>location.href='index.php?s=index/category/category_list';</script>";
            }else{
                echo "<script>alert('商品分类添加失败');</script>";
                echo "<script>location.href='index.php?s=index/category/category_add';</script>";
            }
        }
    }

    /**
     * 删除商品类型的方法
     */
    public function category_del()
    {
        $type_id = input('get.type_id');
        $res = Db::table('category')->delete($type_id);
        if($res){
            echo "<script>alert('商品分类删除成功');</script>";
            echo "<script>location.href='index.php?s=index/category/category_list';</script>";
        }else{
            echo "<script>alert('商品分类删除失败');</script>";
            echo "<script>location.href='index.php?s=index/category/category_list';</script>";
        }
    }
}
