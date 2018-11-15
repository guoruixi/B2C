<?php
namespace app\home\controller;
use think\Controller;
use think\View;
use think\Request;
use think\Db;
use think\captcha\Captcha; 
use think\cache\driver\Redis;
use think\Session;
use QL\QueryList;
class Index extends Controller{
	/**
	 * [login 调用登录模板并判断登录]
	 * @return [type] [description]
	 */
	public function login(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$userInfo = $redis->get(session_id());
		if(!empty($userInfo)){
			echo "<script>alert('您已登陆！')</script>";
			echo "<script>location.href='index.php?s=home/index/index'</script>";
		}else{
			if(Request::instance()->isPost()){
				$data = Request::instance()->post();//获取表单传过来的值
				$userInfo = Db::table('user')->where('nickname',$data['nickname'])->find();
				if(!$this->check_verify($data['verity'])){
					$this->error("您的验证码不正确~");
				}elseif ($data['nickname'] == $userInfo['nickname']) {
					if ($data['nickpwd'] == $userInfo['nickpwd']) {
						$redis->set(session_id(),$userInfo['id']);
						// echo "<script>alert('登录成功！')</script>";
						// echo "<script>location.href='index.php?s=home/index/index'</script>";
						$this->success("登录成功！",'index/index');
					}else{
						$this->error("您的密码不正确~");
					}
				}else{
					$this->error("该用户不存在，请先注册噢~");
				}			
			}else{
				$category =Db::table("category")->field('id,name')->select();//查询分类
				$renderData = [
					'category'=>$category
				];
				return $this->fetch("login",$renderData);
			}	
		}			
	}
	/**
	 * [注册页面]
	 * @return [type] [description]
	 */
	public function register(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$userInfo = $redis->get('userInfo');
		if(Request::instance()->isPost()){
			$data = Request::instance()->post();//获取表单传过来的值
			$userInfo = Db::table('user')->where('nickname',$data['nickname'])->find();
			if(!$this->check_verify($data['verity'])){
				$this->error("您的验证码不正确~");
			}elseif ($data['nickname'] == $userInfo['nickname']) {
				$this->error("该手机号已被注册~");
			}else{
				unset($data['verity']);
				$res = Db::table('user')->insert($data);
				$userId = Db::table('user')->getLastInsID();
				if($res){
					$res = $redis->set(session_id(),$userId);
					echo "<script>alert('注册成功！')</script>";
					echo "<script>location.href='index.php?s=home/index/index'</script>";
				}else{
					$this->error("当前网络拥挤，请稍后重试~");
				}
			}
			die;
		}
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			'category'=>$category
		];
		return $this->fetch('register',$renderData);
	}
	/**
	 * [退出登录]
	 * @return [type] [description]
	 */
	public function exitLogin(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
		// if(empty($user_id)){
		// 	echo "<script>alert('请先登录')</script>";
		// }
		$redis->set(session_id(),'');
		echo "<script>location.href='index.php?s=home/index/login'</script>";
	}
	/**
	 * [生成验证码]
	 * @return [type] [description]
	 */
	public function verify(){
        $captcha = new Captcha();
        //使用中文验证码
        $captcha->useZh = true;
        //验证码过期时间（s）
        // $captcha->expire = 1800;
        //是否画混淆曲线
        $captcha->useCurve = true;
        //是否添加杂点
        $captcha->useNoise = true;
        //验证码位数
        $captcha->length = 4;
        //验证成功后是否重置
        $captcha->reset = true;
        // 设置验证码字符
        $captcha->zhSet = '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
        return $captcha->entry();

	}
	/**
	 * [检测输入的验证码是否正确]
	 * @param  [type] $code [用户输入的验证码字符串]
	 * @param  string $id   [多个验证码标识]
	 * @return [type]       [返回true或者false]
	 */
	function check_verify($code, $id = ''){
	    $captcha = new Captcha();
	    return $captcha->check($code, $id);
	}
	/**
	 * [首页]
	 * @return [type] [description]
	 */
	public function index(){
		$where = "where 1=1";
		$category_id = input('get.category_id');//接收分类id
		$keyword = input('post.keyword');//接收搜索的关键字
		if(!empty($category_id)){
			$where .= " and category_id='$category_id'";
		}
		if(!empty($keyword)){
			$where .= " and name like '%$keyword%'";
		}
		$product_list = Db::query("select * from product $where");//查询产品
		// $product_list = Db::query("select * from product $where");//查询产品
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			"product_list"=>$product_list,
			'category'=>$category
		];
		return $this->fetch("index",$renderData);//查询所有产品数据
	}
	/**
	 * [关于我们]
	 * @return [type] [description]
	 */
	public function about(){
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			'category'=>$category
		];
		return $this->fetch("about",$renderData);
	}
	/**
	 * [产品中心]
	 * @return [type] [description]
	 */
	public function products(){
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			'category'=>$category
		];
		return $this->fetch("products",$renderData);
	}
	/**
	 * [产品详情页]
	 * @return [type] [description]
	 */
	public function productdetail(){
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$id = input('get.id');//接收a标签传来的id值
		$product_info = Db::table('product')->where('id',$id)->find();//查询单条产品数据
		$product_property = Db::table('product_property')->where('product_id',$id)->select();
		//渲染模板
		$renderData = [
			"product_info"=>$product_info,
			'category'=>$category,
			'product_property'=>$product_property,
		];
		return $this->fetch("productdetail",$renderData);//输出模板
	}
	/**
	 * [订单结算]
	 * @return [type] [description]
	 */
	public function checkout(){
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			'category'=>$category
		];
		return $this->fetch("checkout",$renderData);
	}
	/**
	 * [查看购物车]
	 * @return [type] [description]
	 */
	public function shoppingcart(){
		$category =Db::table("category")->field('id,name')->select();//查询分类

		$product_list = Db::view('cart',"counts,product_id,cart_price")
			->view('product','id,name,price,stock,main_img_url',"product.id=cart.product_id")
			->select();
		$total_price = 0;//定义初始的总价格
		foreach ($product_list as $key => $value) {
			$total_price += $value['counts']*$value['cart_price'];
		}
		$renderData = [
			'category'=>$category,
			'product_list'=>$product_list,
			'total_price'=>$total_price,
		];
		return $this->fetch("shoppingcart",$renderData);
	}
	/**
	 * [加入购物车]
	 * @return [type] [description]
	 */
	public function shoppingcart_do(){
		$redis = new Redis();
		$user_id = $redis->get('userInfo');
		if(empty($user_id)){
			echo "<script>alert('请先登录')</script>";
			echo "<script>location.href='index.php?s=home/index/login'</script>";
			die;
		}
		$id = Request::instance()->get('id','');
		$counts = Request::instance()->get('counts','1');
		$cart_price = Request::instance()->get('product_price','');
		$cart_list = Db::table("cart")->where("product_id",$id)->find();
		if(empty($cart_list)){
			$insert = [
				'user_id' => $user_id,
				'counts'=>$counts,
				'product_id'=>$id,
				'cart_price'=>$cart_price,
			];
			$res = Db::table('cart')->insert($insert);
		}else{
			$res = Db::table('cart')
			    ->where('product_id',$id)
    			->setField('counts', $cart_list['counts']+$counts);
		}
		if (Request::instance()->isAjax()){
			if($res){
				echo 1;//加购成功
			}else{
				echo 0;//加购失败
			}
		}else{
			if($res){
				echo "<script>alert('加购成功！')</script>";
				echo "<script>location.href='index.php?s=home/index/shoppingcart'</script>";
				// $this->success("","shoppingcart");
			}
		}
		
	}
	/**
	 * [购物车修改数量及价格]
	 * @return [type] [description]
	 */
    public function changeNum()
    {
    	$counts = Request::instance()->post("counts",'');
    	$id = Request::instance()->post("id",'');
    	//修改数量
    	$res = Db::table('cart')
			    ->where('product_id',$id)
    			->setField('counts', $counts);
    	$product_list = Db::view('cart',"counts,product_id,cart_price")
			->view('product','*',"product.id=cart.product_id")
			->select();
		$total_price = 0;//定义初始的总价格
		$small_price = 0;//定义初始的单个价格
		foreach ($product_list as $key => $value) {
			if($value['id']==$id){
                $small_price=$value['price'] * $value['counts'];
            }
			$total_price += $value['counts']*$value['cart_price'];
			$total_price = round($total_price,2);
		}
		if($res){
			$return = ['code'=>1,'msg'=>'成功','counts'=>$counts,'total_price'=>$total_price,'small_price'=>$small_price];
			echo json_encode($return);//修改成功
		}else{
			$return = ['code'=>0,'msg'=>'失败'];
			echo json_encode($return);//修改失败
		}
    }
    /**
     * [删除购物车数据]
     * @return [type] [description]
     */
    public function deleteCart(){
    	$product_id = Request::instance()->get('product_id','3');
    	if(!empty($product_id)){
    		$res = Db::table('cart')->where('product_id',$product_id)->delete();
    		if($res){
    			echo 1;
    		}else{
    			echo 2;
    		}
    	}else{
    		echo 0;
    	}
    }
	//常见问题
	public function faqs(){
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			'category'=>$category
		];
		return $this->fetch("faqs",$renderData);
	}
	//常见问题
	public function contact(){
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$renderData = [
			'category'=>$category
		];
		return $this->fetch("contact",$renderData);
	}
}