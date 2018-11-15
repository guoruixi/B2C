<?php
namespace app\index\controller;
use think\db;
use think\Controller;
class Base extends Controller
{
    public function initialize()
    {
        $controller=request()->controller();

        $action=request()->action();

        if($controller !== 'index' && $action !== 'login'){
            $userInfo = cookie('userInfo');
            if(empty($userInfo)){
                echo "<script>alert('您尚未登陆');</script>";
                echo "<script>location.href='index.php?s=index/index/login';</script>";
                die;
            }
        }
    }

    public function test($n,$m)
    {
        if($n == 0 && $m == 0){
            return 0;
        }
        if($n == 0 || $m == 0){
            return 1;
        }
        return $this->test($n-1,$m)+$this->test($n,$m-1);
    }
}
