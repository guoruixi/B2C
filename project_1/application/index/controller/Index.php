<?php
namespace app\index\controller;
use think\Db;
class Index extends Base
{
    /**
     * @return 展示登陆页面
     */
    public function login()
    {
        if(request()->isGet()){
            return view('login');
        }
        if(request()->isPost()){
            $data = input('post.');
            if(!captcha_check($data['verify'])){
                echo "<script>alert('验证码输入错误');</script>";
                echo "<script>location.href='index.php?s=index/index/login';</script>";
            };

            $sql = "select * from `user` WHERE nickname = '{$data['nickname']}' AND nickpwd = '{$data['nickpwd']}'";
            $res = Db::table('user')->query($sql);

            if(!empty($res)){
                $userInfo = [
                    'user_id' => $res[0]['id'],
                    'username' => $res[0]['nickname'],
                ];
                cookie('userInfo',$userInfo, 3600);
                echo "<script>alert('登陆成功');</script>";
                echo "<script>location.href='index.php?s=index/index/index';</script>";
            }else{
                echo "<script>alert('登陆失败');</script>";
                echo "<script>location.href='index.php?s=index/index/login';</script>";
            }
        }
    }


    /**
     * @return 展示注册页面
     */
    public function register()
    {
        return view('register');
    }

    /**
     * 执行注册的方法
     */
    public function register_do()
    {
        $data = input('post.');
        $add_data['nickname'] = $data['username'];
        $add_data['nickpwd'] = $data['password'];
        $add_data['create_time'] = time();
        $res = Db::table('user')->insert($add_data);
        if($res){
            echo "<script>alert('用户注册成功');</script>";
            echo "<script>location.href='index.php?s=index/index/login';</script>";
        }else{
            echo "<script>alert('用户注册失败');</script>";
            echo "<script>location.href='index.php?s=index/index/register';</script>";
        }
    }

    /**
     * @return 展示后台首页
     */
    public function index()
    {
        $username = cookie('userInfo')['username'];
        return view('index',['username' => $username]);
    }
}
