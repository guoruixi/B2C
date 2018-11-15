<?php
namespace app\index\controller;
use think\db;
use think\Controller;
class Banner extends Controller
{
    /**
     * @return 展示轮播图
     */
    public function banner_list()
    {
        $sql = "SELECT banner_item.id as banner_item_id,image.id as image_id,banner.description,image.url from banner_item JOIN image on banner_item.img_id = image.id JOIN banner ON banner_item.banner_id = banner.id WHERE banner_item.is_del = 0";
        $data = Db::query($sql);
        return view('banner_list',['data' => $data]);
    }

    /**
     * @return 展示轮播图的添加页面
     */
    public function banner_add()
    {
        $banner_data = Db::table('banner')->select();
        $product_data = Db::table('product')->limit(10)->select();
        $data = [
            'banner_data' => $banner_data,
            'product_data' => $product_data,
        ];
        return view('banner_add',['data' => $data]);
    }

    /**
     * 执行添加轮播图的方法
     */
    public function banner_add_do()
    {
        $add_data['from'] = input('post.from');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('banner');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move("uploads/");
        if($info){
            $add_data['url'] = 'uploads/'.$info->getSaveName();
            Db::table('image')->insert($add_data);
            $image_id = Db::table('image')->getLastInsID();
            $add_banner_data['img_id'] = $image_id;
            $add_banner_data['key_word'] = input('key_word');
            $add_banner_data['type'] = 1;
            $add_banner_data['banner_id'] = input('banner_id');
            $res = Db::table('banner_item')->insert($add_banner_data);
            if($res){
                echo "<script>alert('图片添加成功');</script>";
                echo "<script>location.href='index.php?s=index/banner/banner_list';</script>";
            }else{
                echo "<script>alert('图片添加失败');</script>";
                echo "<script>location.href='index.php?s=index/banner/banner_add';</script>";
            }
        }

    }

    /**
     * 删除轮播图图片
     */
    public function banner_del()
    {
        $banner_item_id = input('get.banner_item_id');
        $res = Db::table('banner_item')
            ->where('id', $banner_item_id)
            ->update(['is_del' => 1]);
        if($res){
            echo "<script>alert('图片删除成功');</script>";
            echo "<script>location.href='index.php?s=index/banner/banner_list';</script>";
        }else{
            echo "<script>alert('图片删除失败');</script>";
            echo "<script>location.href='index.php?s=index/banner/banner_list';</script>";
        }
    }

    public function theme_list()
    {
        $sql = "SELECT a.id,a.name,a.description,b.url as topic_img_url,c.url as head_img_url from theme as a JOIN image as b on a.topic_img_id = b.id JOIN image as c ON a.head_img_id = c.id WHERE a.is_del = 0";
        $data = Db::query($sql);
        return view('theme_list',['data' => $data]);
    }

    public function theme_img_save()
    {
        $id = input('get.id');

        $sql = "SELECT a.id,a.name,a.description,b.url as topic_img_url,c.url as head_img_url from theme as a JOIN image as b on a.topic_img_id = b.id JOIN image as c ON a.head_img_id = c.id WHERE a.id = $id";
        $data = Db::query($sql);
        return view('theme_img_save',['data' => $data]);
    }

    public function theme_img_save_do()
    {
        $post_data = input('post.');
        if(!empty($_FILES['topic_img_url']['name']) && empty($_FILES['head_img_url']['name'])){
        //当只上传主体图片的时候
            $fileName = substr($_FILES['topic_img_url']['name'],strpos($_FILES['topic_img_url']['name'],'.')+1);
            $topic_img_url = 'uploads/'.md5(uniqid().time()).$fileName;
            move_uploaded_file($_FILES['topic_img_url']['tmp_name'],$topic_img_url);

            Db::table('image')->insert(['url' => $topic_img_url,'from' => 1]);
            $topic_img_id = Db::table('image')->getLastInsID();

            $res = Db::table('theme')
                ->where('id', $post_data['id'])
                ->update([
                    'name' => $post_data['name'],
                    'description' => $post_data['description'],
                    'topic_img_id'=> $topic_img_id
                ]);

        }elseif(empty($_FILES['topic_img_url']['name']) && !empty($_FILES['head_img_url']['name'])){
        //当只上传列表图片的时候
            $fileName = substr($_FILES['head_img_url']['name'],strpos($_FILES['head_img_url']['name'],'.')+1);
            $head_img_url = 'uploads/'.md5(uniqid().time()).$fileName;
            move_uploaded_file($_FILES['head_img_url']['tmp_name'],$head_img_url);

            Db::table('image')->insert(['url' => $head_img_url,'from' => 1]);
            $head_img_id = Db::table('image')->getLastInsID();

            $res = Db::table('theme')
                ->where('id', $post_data['id'])
                ->update([
                    'name' => $post_data['name'],
                    'description' => $post_data['description'],
                    'head_img_id'=> $head_img_id
                ]);
        }elseif(!empty($_FILES['topic_img_url']['name']) && !empty($_FILES['head_img_url']['name'])){
        //当两张图片同时上传的时候
            $file = request()->file('topic_img_url');
            $info = $file->move("uploads/");
            $topic_img_url = 'uploads/' . $info->getSaveName();
            Db::table('image')->insert(['url' => $topic_img_url,'from' => 1]);
            $topic_img_id = Db::table('image')->getLastInsID();

            $fileName = substr($_FILES['head_img_url']['name'],strpos($_FILES['head_img_url']['name'],'.')+1);
            $head_img_url = 'uploads/'.md5(uniqid().time()).$fileName;
            move_uploaded_file($_FILES['head_img_url']['tmp_name'],$head_img_url);

            Db::table('image')->insert(['url' => $head_img_url,'from' => 1]);
            $head_img_id = Db::table('image')->getLastInsID();

            $res = Db::table('theme')
                ->where('id', $post_data['id'])
                ->update([
                    'name' => $post_data['name'],
                    'description' => $post_data['description'],
                    'topic_img_id'=> $topic_img_id,
                    'head_img_id'=> $head_img_id,
                ]);
        }else{
        //当没有图片上传的时候
            $res = Db::table('theme')
                ->where('id', $post_data['id'])
                ->update([
                    'name' => $post_data['name'],
                    'description' => $post_data['description'],
                ]);
        }
        if($res){
            echo "<script>alert('主题内容更新成功');</script>";
            echo "<script>location.href='index.php?s=index/banner/theme_list';</script>";
        }else{
            echo "<script>alert('主题内容更新失败');</script>";
            echo "<script>location.href='index.php?s=index/banner/theme_list';</script>";
        }
    }

    public function theme_add()
    {
        return view('theme_add');
    }

    public function theme_add_do()
    {
        if(!empty($_FILES['topic_img_url']['name']) && !empty($_FILES['head_img_url']['name'])){
            $post_data = input('post.');
            $data['name'] = $post_data['name'];
            $data['description'] = $post_data['description'];
            //上传第一张图片
            $file = request()->file('topic_img_url');
            $info = $file->move("uploads/");
            $topic_img_url = 'uploads/' . $info->getSaveName();
            Db::table('image')->insert(['url' => $topic_img_url,'from' => 1]);
            $topic_img_id = Db::table('image')->getLastInsID();
            //上传第二张图片
            $fileName = substr($_FILES['head_img_url']['name'],strpos($_FILES['head_img_url']['name'],'.')+1);
            $head_img_url = 'uploads/'.md5(uniqid().time()).$fileName;
            move_uploaded_file($_FILES['head_img_url']['tmp_name'],$head_img_url);
            //入库
            Db::table('image')->insert(['url' => $head_img_url,'from' => 1]);
            $head_img_id = Db::table('image')->getLastInsID();

            $data['topic_img_id'] = $topic_img_id;
            $data['head_img_id'] = $head_img_id;
            $res = Db::table('theme')->insert($data);
            if($res){
                echo "<script>alert('新的主题添加成功');</script>";
                echo "<script>location.href='index.php?s=index/banner/theme_list';</script>";
            }else{
                echo "<script>alert('新的主题添加失败');</script>";
                echo "<script>location.href='index.php?s=index/banner/theme_list';</script>";
            }
        }else{
            echo "<script>alert('必须有图片上传');</script>";
            echo "<script>location.href='index.php?s=index/banner/theme_add';</script>";
        }

    }

    public function theme_del()
    {
        $theme_id = input('get.id');
        $res = Db::table('theme')
            ->where('id', $theme_id)
            ->update([
                'is_del'=> 1,
                'delete_time'=> time(),
            ]);
        if($res){
            echo "<script>alert('主题删除成功');</script>";
            echo "<script>location.href='index.php?s=index/banner/theme_list';</script>";
        }else{
            echo "<script>alert('主题删除失败');</script>";
            echo "<script>location.href='index.php?s=index/banner/theme_list';</script>";
        }
    }
}
