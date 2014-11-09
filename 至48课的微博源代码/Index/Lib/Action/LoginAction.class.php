<?php
/**
 * 注册与登录控制器
 */
Class LoginAction extends Action {

	/**
	 * 登录页面
	 */
	Public function index () {
		$this->display();
	}

	/**
	 * 登录表单处理
	 */
	Public function login () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}

		//提取表单内容
		$account = $this->_post('account');
		$pwd = $this->_post('pwd', 'md5');

		$where = array('account' => $account);

		$user = M('user')->where($where)->find();

		if (!$user || $user['password'] != $pwd) {
			$this->error('用户名或者密码不正确');
		}

		if ($user['lock']) {
			$this->error('用户被锁定');
		}

		//处理下一次自动登录
		if (isset($_POST['auto'])) {
			$account = $user['account'];
			$ip = get_client_ip();
			$value = $account . '|' . $ip;
			$value = encryption($value);
			@setcookie('auto', $value, C('AUTO_LOGIN_TIME'), '/');
		}

		//登录成功写入SESSION并且跳转到首页
		session('uid', $user['id']);

		header('Content-Type:text/html;Charset=UTF-8');
		redirect(__APP__, 3, '登录成功，正在为您跳转...');
	}

	/**
	 * 注册页面
	 */
	Public function register () {
		$this->display();
	}

	/**
	 * 注册表单处理
	 */
	Public function runRegis () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}
		if ($_SESSION['verify'] != md5($_POST['verify'])) {
			$this->error('验证码错误');
		}
		if ($_POST['pwd'] != $_POST['pwded']) {
			$this->error('两次密码不一致');
		}

		//提取POST数据
		$data = array(
			'account' => $this->_post('account'),
			'password' => $this->_post('pwd', 'md5'),
			'registime' => $_SERVER['REQUEST_TIME'],
			'userinfo' => array(
				'username' => $this->_post('uname')
				)
			);

		$id = D('UserRelation')->insert($data);
		if ($id) {
			//插入数据成功后把用户ID写SESSION
			session('uid', $id);

			//跳转至首页
			header('Content-Type:text/html;Charset=UTF-8');
			redirect(__APP__, 3, '注册成功，正在为您跳转...');
		} else {
			$this->error('注册失败，请重试...');
		}
	}

	/**
	 * 获取验证码
	 */
	Public function verify () {
		import('ORG.Util.Image');
		Image::buildImageVerify(4, 1, 'png');
	}

	/**
	 * 异步验证账号是否已存在
	 */
	Public function checkAccount () {
		if (!$this->isAjax()) {
			halt('页面不存在');
		}
		$account = $this->_post('account');
		$where = array('account' => $account);
		if (M('user')->where($where)->getField('id')) {
			echo 'false';
		} else {
			echo 'true';
		}
	}

	/**
	 * 异步验证昵称是否已存在
	 */
	Public function checkUname () {
		if (!$this->isAjax()) {
			halt('页面不存在');
		}
		$username = $this->_post('uname');
		$where = array('username' => $username);
		if (M('userinfo')->where($where)->getField('id')) {
			echo 'false';
		} else {
			echo 'true';
		}
	}

	/**
	 * 异步验证验证码
	 */
	Public function checkVerify () {
		if (!$this->isAjax()) {
			halt('页面不存在');
		}
		$verify = $this->_post('verify');
		if ($_SESSION['verify'] != md5($verify)) {
			echo 'false';
		} else {
			echo 'true';
		}
	}
}
?>