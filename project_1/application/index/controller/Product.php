<?php
namespace app\index\controller;
use think\db;
use think\Controller;
use think\File;
class Product extends Controller
{

    /**
     * @return 展示商品添加页面
     */
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

    /**
     * ajax获取属性信息
     */
    public function get_attr()
    {
        $type_id = input('post.type_id');
        $data = Db::table('attr')->where('type_id',$type_id)->select();
        echo json_encode($data);
    }

    /**
     * @return 商品列表展示页面
     */
    public function product_list()
    {
        $data = Db::query("SELECT p.id,p.`name` as product_name,p.price,p.stock,p.main_img_url,c.`name` from product as p JOIN category as c ON p.category_id = c.id WHERE p.is_del = 0");
        return view('product_list',['data' => $data]);
    }

    /**
     * @return 展示商品详情添加页面
     */
    public function property()
    {
        $product_id = input('get.product_id');
        return view('property_add',['product_id' => $product_id]);
    }

    /**
     * 执行添加商品详情
     */
    public function property_add()
    {
        $data = input('post.');
        $img = [];
        // 获取表单上传文件
        $files = request()->file('img');
        foreach($files as $file){
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move("uploads/");
            if($info){
                $img[] = 'uploads/'.$info->getSaveName();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
        $add_image_data = [];
        $add_image_id = [];
        foreach($img as $key => $val)
        {
            $add_image_data[$key]['url'] = $val;
            $add_image_data[$key]['from'] = 1;

            Db::table('image')->insert($add_image_data[$key]);
            $add_image_id[] = Db::name('image')->getLastInsID();
        }
        $product_image_data = [];
        foreach($add_image_id as $key => $val)
        {
            $product_image_data[$key]['img_id'] = $val;
            $product_image_data[$key]['order'] = 1;
            $product_image_data[$key]['product_id'] = $data['product_id'];
        }
        Db::table('product_image')->insertAll($product_image_data);

        $add_data = [];
        foreach($data['name'] as $key => $val)
        {
            $add_data[$key]['name'] = $val;
            $add_data[$key]['detail'] = $data['detail'][$key];
            $add_data[$key]['product_id'] = $data['product_id'];
        }
        $res = Db::table('product_property')->insertAll($add_data);

        if($res){
            echo "<script>alert('商品描述添加成功');</script>";
            echo "<script>location.href='index.php?s=index/product/product_list';</script>";
        }else{
            echo "<script>alert('商品描述添加失败');</script>";
            echo "<script>location.href='index.php?s=index/product/product_list';</script>";
        }
    }

    /**
     * @return 查看商品详情
     */
    public function check_desc()
    {
        $product_id = input('get.product_id');

        $img_data = Db::table('product_image')
            ->alias('a')
            ->join('image b','a.img_id = b.id')
            ->where('a.product_id',$product_id)
            ->select();

        $property_data = Db::table('product_property')->where('product_id',$product_id)->select();
        $arr = [
            'img_data' => $img_data,
            'property_data' => $property_data,
        ];

        return view('check_desc',['arr' => $arr]);
    }

    /**
     * 商品信息添加页面
     */
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
            $product_data['create_time'] = time();
            Db::table('product')->insert($product_data);
            $product_id = Db::name('product')->getLastInsID();
            if($product_id){
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
    }

    /**
     * @return 展示sku添加页面
     */
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

    /**
     * 执行商品sku添加方法
     */
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

    /**
     * 商品删除
     */
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
