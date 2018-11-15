<?php
namespace app\index\controller;
use think\db;
use think\Controller;
class Attr extends Controller
{
    /**
     * @return 商品属性添加
     * 查询商品分类并赋值
     */
    public function attr_add()
    {
        $data = Db::table('type')->where('is_del',0)->select();
        return view('attr_add',['data' => $data]);
    }

    /**
     * @return 商品属性展示
     * 根据分类id查询出商品的属性
     */
    public function attr_list()
    {
        $type_id = input('get.type_id');
        $where = '1 = 1';
        if(!empty($type_id)){
            $where .= " and `type`.type_id = $type_id";
        }
        $data = Db::query("select * from `type` JOIN attr on `type`.type_id = attr.type_id WHERE $where and attr.is_del = 0");
        return view('attr_list',['data' => $data]);
    }

    /**
     * 商品属性添加
     */
    public function attr_add_do()
    {
        $data = input('post.');
        $res = Db::table('attr')->insert($data);
        if($res){
            echo "<script>alert('商品属性添加成功');</script>";
            echo "<script>location.href='index.php?s=index/attr/attr_list';</script>";
        }else{
            echo "<script>alert('商品属性添加失败');</script>";
            echo "<script>location.href='index.php?s=index/attr/attr_add';</script>";
        }
    }

    /**
     * 商品属性删除
     * 假删除只修改商品删除状态
     */
    public function attr_del()
    {
        $attr_id = input('get.attr_id');
        $res = Db::table('attr')
            ->where('attr_id', $attr_id)
            ->update(['is_del' => 1]);
        if($res){
            echo "<script>alert('商品属性删除成功');</script>";
            echo "<script>location.href='index.php?s=index/attr/attr_list';</script>";
        }else{
            echo "<script>alert('商品属性删除失败');</script>";
            echo "<script>location.href='index.php?s=index/attr/attr_list';</script>";
        }
    }
}
