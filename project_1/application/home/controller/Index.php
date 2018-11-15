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
			// echo "<script>alert('您已登陆！')</script>";
			// echo "<script>location.href='index.php?s=home/index/index'</script>";
			$this->success("您已登陆！",'index/index');
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
					// echo "<script>alert('注册成功！')</script>";
					// echo "<script>location.href='index.php?s=home/index/index'</script>";
					$this->success("注册成功！",'index/index');
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
		$redis->set(session_id(),'');
		$this->redirect("Index/login");
		// echo "<script>location.href='index.php?s=home/index/login'</script>";
		// $this->success("您已退出登录","Index/login");
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
        $captcha->expire =1800;
        //是否画混淆曲线
        $captcha->useCurve =true;
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
		$banner = Db::table("image")->field('id,url')->limit(5)->select();//查询轮播图
		// var_dump($banner);die;
		$search = input('get.search');//接收搜索的关键字
        $category =Db::table("category")->field('id,name')->select();//查询分类
        $size = 8;
        $page = input('get.page');
        $page = empty($page) ? 1 : $page ;
        $limit = ($page-1)*$size;
        if(empty($search)){
            //当搜索条件为空的时候
            $count = Db::table('product')->count();
            $pageNum = ceil($count/$size);
            $product_list = Db::table('product')->where('is_del',0)->limit($limit,$size)->select();
        }else{
            //当有搜索条件的时候
            $count_sql = "select count(1) from product WHERE name like '%$search%'";
            $count = Db::query($count_sql);
            $count = $count[0]['count(1)'];
            $data_sql = "select * from product WHERE name like '%$search%' limit $limit,$size";
            $pageNum = ceil($count/$size);
            $product_list = Db::query($data_sql);
        }
        foreach ($product_list as $key => $value) {
        	$product_list[$key]['alt_name'] = $value['name'];
        	$product_list[$key]['name'] = mb_substr($value['name'],0,8).'…';
        }
        if(request()->isAjax()){
            $prev_page = $page - 1 < 1 ? 1 : $page - 1 ;
            $next_page = $page + 1 > $pageNum ? $pageNum : $page + 1 ;
            $return_data = [
                'product_list'=>$product_list,
                'prev_page'=>$prev_page,
                'next_page'=>$next_page,
            ];
            echo json_encode($return_data);
        }else{
            $renderData = [
                "product_list"=>$product_list,
                'category'=>$category,
                'page'=>$page,
                'pageNum'=>$pageNum,
                'banner'=>$banner
            ];
            return $this->fetch("index",$renderData);//查询所有产品数据
        }
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

        $category_id = input('get.category_id');

        if(empty($category_id)){
            $category_id = $category[0]['id'];
        }
        $page = input('get.page');
        $page = empty($page) ? 1 : $page ;
        $count = Db::table('product')->where('category_id',$category_id)->count();
        $size = 8;
        $pageNum = ceil($count/$size);
        $limit = ($page-1)*$size;

        $category_name = $category[0]['name'];

        $product_data = Db::table('product')->where('category_id',$category_id)->limit($limit,$size)->select();
		foreach ($product_data as $key => $value) {
        	$product_data[$key]['alt_name'] = $value['name'];
        	$product_data[$key]['name'] = mb_substr($value['name'],0,8).'…';
        }
        if (request()->isAjax()) {
            $prev_page = $page - 1 < 1 ? 1 : $page - 1 ;
            $next_page = $page + 1 > $pageNum ? $pageNum : $page + 1 ;
            $return_data = [
                'product_data'=>$product_data,
                'prev_page'=>$prev_page,
                'next_page'=>$next_page,
            ];
            echo json_encode($return_data);
        }else{
            $renderData = [
                'category'=>$category,
                'product_data'=>$product_data,
                'category_name'=>$category_name,
                'category_id'=>$category_id,
                'page'=>$page,
                'pageNum'=>$pageNum,
            ];
            return $this->fetch("products",$renderData);
        }
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
	 * [添加订单]
	 */
	public function add_order(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
		$cart_str=trim($_POST['cart_str'],',');
		$sql="select c.counts as count,c.product_id,c.counts*p.price as cart_price from cart as c inner join product as p on c.product_id=p.id where c.id in ($cart_str)";
		$cart_data=Db::query($sql);
		$sum_price=0;
		$total_count=0;
		foreach ($cart_data as $key => $value) {
			$sum_price=$sum_price+$value['cart_price'];
			$total_count=$total_count+$value['count'];
		}
		$order_data=[
			"user_id"=>$user_id,
			"order_no"=>time().mt_rand(1000,9999),
			"total_price"=>$sum_price,
			"create_time"=>time(),
			"total_count"=>$total_count,
		];
		$res=Db::table("order")->insert($order_data);
		$order_id = Db::name('order')->getLastInsID();
		foreach ($cart_data as $key => $value) {
			unset($cart_data[$key]["cart_price"]);
            $cart_data[$key]['order_id']=@$order_id;
            $cart_data[$key]['update_time']=time();
        }
        $res=Db::name('order_product')->insertAll($cart_data);
        Db::table("cart")->where("id","in",$cart_str)->where("user_id",$user_id)->update(["is_del"=>1]);
        echo $order_id;
	}
	/**
	 * [订单结算]
	 * @return [type] [description]
	 */
	public function checkout(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
		if(!empty($user_id)){
			$msg = "您的收货地址是：";
		}else{
			$msg = "";
		}
		// echo $msg;die;
		$order_id=$_GET['order_id'];
		$category =Db::table("category")->field('id,name')->select();//查询分类
		$sql="select*from order_product where order_id = $order_id";
		$cart_data=Db::query($sql);
		// var_dump($cart_data);die;
		$sum_price=(Db::table("order")->where("id",$order_id)->field('total_price')->find())['total_price'];

		$address = Db::table("user_address")->field("id,name,mobile,province,city,country,detail")->where('user_id',$user_id)->select();//查询用户收货地址

		$product_list=Db::query("select p.name,p.main_img_url,op.count,p.price,op.count*p.price as order_price_one from product as p inner join order_product as op on op.product_id=p.id inner join `order` as od on op.order_id=od.id where od.id=$order_id");
		$total_price = 0;//定义初始的总价格
		$renderData = [
			'category'=>$category,
			"cart_data"=>$cart_data,
			"sum_price"=>$sum_price,
			'msg'=>$msg,
			// "product_list",$product_list,
			// "address",$address
		];
		$this->assign("product_list",$product_list);
		$this->assign("address",$address);
		return $this->fetch("checkout",$renderData);
	}
	/**
	 * [查看购物车]
	 * @return [type] [description]
	 */
	public function shoppingcart(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
		if(empty($user_id)){
			$this->error('请先登录','index/login');
			// echo "<script>alert('请先登录~');location.href='index.php?s=home/index/login'</script>";die;
		}
		$category =Db::table("category")->field('id,name')->select();//查询分类

		$product_list = Db::view('cart',"id as cart_id,counts,product_id,cart_price")
			->view('product','id,name,price,stock,main_img_url',"product.id=cart.product_id")
			->where('user_id',$user_id)
			->where('cart.is_del',0)
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
	 * [添加用户收货地址]
	 */
	public function add_userAddress(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$data = input('post.');
		$data['user_id'] = $redis->get(session_id());
		$res = Db::table('user_address')->insert($data);
		$address = Db::table("user_address")->field("id,name,mobile,province,city,country,detail")->where('user_id',$data['user_id'])->select();
		if($res){
			echo json_encode(['code'=>'200','address'=>$address]);//用户收货地址添加成功
		}else{
			echo 0;//失败
		}
	}
	/**
	 * [加入购物车]
	 * @return [type] [description]
	 */
	public function shoppingcart_do(){
		$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
		if(empty($user_id)){
			$this->error('请先登录','index/login');die;
		}
		$id = Request::instance()->get('id','');
		$counts = Request::instance()->get('counts','1');
		$cart_price = Request::instance()->get('product_price','');
		$cart_list = Db::table("cart")->where("product_id",$id)->where('is_del','0')->find();
		if(empty($cart_list)){
			$insert = [
				'user_id' => $user_id,
				'counts'=>$counts,
				'product_id'=>$id,
				'cart_price'=>$cart_price,
			];
			$res = Db::table('cart')->insert($insert);
		}else{
			// if($cart_list['is_del'] == 1){
			// 	$insert = [
			// 		'user_id' => $user_id,
			// 		'counts'=>$counts,
			// 		'product_id'=>$id,
			// 		'cart_price'=>$cart_price,
			// 	];
			// 	$res = Db::table('cart')->insert($insert);
			// }else{
				$res = Db::table('cart')
			    	->where('product_id',$id)
    				->setField('counts', $cart_list['counts']+$counts);
			// }			
		}
		if (Request::instance()->isAjax()){
			if($res){
				echo 1;//加购成功
			}else{
				echo 0;//加购失败
			}
		}else{
			if($res){
				// echo "<script>alert('加购成功！')</script>";
				// echo "<script>location.href='index.php?s=home/index/shoppingcart'</script>";
				$this->success("加购成功！","index/shoppingcart");
			}
		}
		
	}
	/**
	 * [购物车修改数量及价格]
	 * @return [type] [description]
	 */
    public function changeNum()
    {
    	$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
    	$counts = Request::instance()->post("counts",'');
    	$id = Request::instance()->post("id",'');
    	//修改数量
    	$res = Db::table('cart')
			    ->where('product_id',$id)
    			->setField('counts', $counts);
    	$product_list = Db::view('cart',"counts,product_id,cart_price")
			->view('product','*',"product.id=cart.product_id")
			->where('user_id',$user_id)
			->where('cart.is_del',0)
			->select();
		$total_price = 0;//定义初始的总价格
		$sigal_price = 0;//定义初始的单个价格
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

    //收货地址
    public function contact(){
    	$redis = new Redis();
		$session = new Session();
		$session->start();
		$user_id = $redis->get(session_id());
		if(empty($user_id)){
			$this->error('请先登录','index/login');die;
		}
        $category =Db::table("category")->field('id,name')->select();//查询分类
        $address = Db::table("user_address")->field("id,name,mobile,province,city,country,detail")->where('user_id',$user_id)->select();//查询用户收货地址
        $renderData = [
            'category'=>$category,
            'address'=>$address
        ];
        return $this->fetch("contact",$renderData);
    }
    public function pay(){
    	//合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner']		= '2088121321528708';
		//收款支付宝账号
        $alipay_config['seller_email']	= 'itbing@sina.cn';
		//安全检验码，以数字和字母组成的32位字符
        $alipay_config['key']			= '1cvr0ix35iyy7qbkgs3gwymeiqlgromm';
		//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
		//签名方式 不需修改
        $alipay_config['sign_type']    = strtoupper('MD5');
		//字符编码格式 目前支持 gbk 或 utf-8
		//$alipay_config['input_charset']= strtolower('utf-8');
		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		//$alipay_config['cacert']    = getcwd().'\\cacert.pem';
		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport']    = 'http';
// ******************************************************配置 end*************************************************************************************************************************

// ******************************************************请求参数拼接 start*************************************************************************************************************************
        $order_id = time().rand(100000,999999);
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "partner" => $alipay_config['partner'], // 合作身份者id
            "seller_email" => $alipay_config['seller_email'], // 收款支付宝账号
            "payment_type"	=> '1', // 支付类型
            "notify_url"	=> "http://47.94.17.35/flower_project/public/index.php/Home/Index/index", // 服务器异步通知页面路径
            "return_url"	=> "http://47.94.17.35/flower_project/public/index.php/Home/Index/index", // 页面跳转同步通知页面路径
            "out_trade_no"	=> md5(time()), // 商户网站订单系统中唯一订单号
            "subject"	=> "微鲜商水果", // 订单名称
            "total_fee"	=> "0.01", // 付款金额
            "body"	=> "xxxxxx", // 订单描述 可选
            "show_url"	=> "", // 商品展示地址 可选
            "anti_phishing_key"	=> "", // 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
            "exter_invoke_ip"	=> "", // 客户端的IP地址
            "_input_charset"	=> 'utf-8', // 字符编码格式
        );
		// 去除值为空的参数
        foreach ($parameter as $k => $v) {
            if (empty($v)) {
                unset($parameter[$k]);
            }
        }
		// 参数排序
        ksort($parameter);
        reset($parameter);

		// 拼接获得sign
        $str = "";
        foreach ($parameter as $k => $v) {
            if (empty($str)) {
                $str .= $k . "=" . $v;
            } else {
                $str .= "&" . $k . "=" . $v;
            }
        }
        $parameter['sign'] = md5($str . $alipay_config['key']);	// 签名
        $parameter['sign_type'] = $alipay_config['sign_type'];
// ******************************************************请求参数拼接 end*************************************************************************************************************************


// ******************************************************模拟请求 start*************************************************************************************************************************
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='https://mapi.alipay.com/gateway.do?_input_charset=utf-8' method='get'>";
        foreach ($parameter as $k => $v) {
            $sHtml.= "<input type='text' name='" . $k . "' value='" . $v . "'/>";
        }

        $sHtml .= '<input type="submit" value="去支付">';

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        /*******************************************************模拟请求 end**************************************************************************************************************************/
		//var_dump($sHtml);
        echo $sHtml;

    }
}