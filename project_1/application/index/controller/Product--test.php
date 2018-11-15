<?php
namespace app\index\controller;
use think\db;
use think\Controller;
use think\File;
class Product extends Controller
{
    public function product_add()
    {
        $type_data = Db::table('type')->where('is_del',0)->select();
        $category_data = Db::table('category')->select();
        $arr = [
            'type_data' => $type_data,
            'category_data' => $category_data,
        ];
        return view('product_add',['arr' => $arr]);
    }

    public function get_attr()
    {
        $type_id = input('post.type_id');
        $data = Db::table('attr')->where('type_id',$type_id)->select();
        echo json_encode($data);
    }

    public function product_list()
    {
        $data = Db::query("SELECT p.id,p.`name` as product_name,p.price,p.stock,p.main_img_url,c.`name`,t.type_name from product as p JOIN category as c ON p.category_id = c.id JOIN type as t ON p.type_id = t.type_id WHERE p.is_del = 0");
        return view('product_list',['data' => $data]);
    }

    public function product_add_do()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('product_img');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move("uploads/");
        if($info){
            $product_data['main_img_url'] = 'uploads/'.$info->getSaveName();
            $data = input('post.');
            $product_data['name'] = $data['product_name'];
            $product_data['category_id'] = $data['category_id'];
            $product_data['stock'] = $data['stock'];
            $product_data['price'] = $data['price'];
            $product_data['type_id'] = $data['type_id'];
            $product_data['create_time'] = time();

            $product_attr_data = [];

            Db::table('product')->insert($product_data);
            $product_id = Db::name('product')->getLastInsID();

            foreach($data['attr_id'] as $key => $val)
            {
                $product_attr_data[$key]['product_id'] = $product_id;
                $product_attr_data[$key]['attr_id'] = $val;
                $product_attr_data[$key]['attr_value'] = $data['product_values'][$key];
                $product_attr_data[$key]['product_attr_price'] = $data['attr_price'][$key];
            }
            if($product_id){
                $res = Db::name('product_attr')->insertAll($product_attr_data);
                if($res){
                    echo "<script>alert('商品添加成功');</script>";
                    echo "<script>location.href='index.php?s=index/product/product_list';</script>";
                }else{
                    echo "<script>alert('商品添加失败');</script>";
                    echo "<script>location.href='index.php?s=index/product/product_add';</script>";
                }
            }else{
                echo "<script>alert('商品添加失败');</script>";
                echo "<script>location.href='index.php?s=index/product/product_add';</script>";
            }

        }else{
            echo "<script>alert('商品添加失败');</script>";
            echo "<script>location.href='index.php?s=index/product/product_add';</script>";
        }
    }

    public function sku_add()
    {
        $product_id = input('get.product_id');
        $sql = "SELECT * from product_attr join attr on product_attr.attr_id = attr.attr_id WHERE product_attr.product_id = $product_id";
        $data = Db::query($sql);
        $arr = [
            'data' => $data,
            'product_id' => $product_id
        ];
        return view('sku_add',['arr' => $arr]);
    }

    public function sku_add_do()
    {
        $post_data = input('post.');
        $str = '';
        foreach($post_data['product_attr_values'] as $key => $val)
        {
            $str .= $val.",";
        }
        $str = rtrim($str,',');
        $add_data['product_id'] = $post_data['product_id'];
        $add_data['product_attr_ids'] = $str;
        $add_data['sku_price'] = $post_data['sku_price'];
        $add_data['sku_num'] = $post_data['sku_num'];
        $res = Db::name('sku')->insert($add_data);
        if($res){
            echo "<script>alert('商品库存添加成功');</script>";
            echo "<script>location.href='index.php?s=index/product/product_list';</script>";
        }else{
            echo "<script>alert('商品库存添加失败');</script>";
            echo "<script>location.href='index.php?s=index/product/product_list';</script>";
        }
    }

    public function product_del()
    {
        $product_id = input('get.product_id');
        $res = Db::table('product')
            ->where('id', $product_id)
            ->update(['is_del' => 1,'delete_time' => time()]);
        if($res){
            echo "<script>alert('商品删除成功');</script>";
            echo "<script>location.href='index.php?s=index/product/product_list';</script>";
        }else{
            echo "<script>alert('商品删除失败');</script>";
            echo "<script>location.href='index.php?s=index/product/product_list';</script>";
        }
    }
}
