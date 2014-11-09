<?php
/**
 * 账号设置控制器
 */
Class UserSettingAction extends CommonAction {

	/**
	 * 用户基本信息设置视图
	 */
	Public function index () {
		$where = array('uid' => session('uid'));
		$field = array('username', 'truename', 'sex', 'location', 'constellation', 'intro', 'face180');
		$user = M('userinfo')->field($field)->where($where)->find();
		$this->user = $user;
		$this->display();
	}

	/**
	 * 修改用户基本信息
	 */
	Public function editBasic () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}
		header('Content-Type:text/html;Charset=UTF-8');
		$data = array(
			'username' => $this->_post('nickname'),
			'truename' => $this->_post('truename'),
			'sex' => (int) $_POST['sex'],
			'location' => $this->_post('province') . ' ' . $this->_post('city'),
			'constellation' => $this->_post('night'),
			'intro' => $this->_post('intro')
			);
		$where = array('uid' => session('uid'));
		if (M('userinfo')->where($where)->save($data)) {
			$this->success('修改成功', U('index'));
		} else {
			$this->error('修改失败');
		}
	}

	/**
	 * 修改用户头像
	 */
	Public function editFace () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}
		$db = M('userinfo');
		$where = array('uid' => session('uid'));
		$field = array('face50', 'face80', 'face180');
		$old = $db->where($where)->field($field)->find();
		if ($db->where($where)->save($_POST)) {
			if (!empty($old['face180'])) {
				@unlink('./Uploads/Face/' . $old['face180']);
				@unlink('./Uploads/Face/' . $old['face80']);
				@unlink('./Uploads/Face/' . $old['face50']);
			}
			$this->success('修改成功', U('index'));
		} else {
			$this->error('修改失败，请重试...');
		}
	}

	/**
	 * 修改密码
	 */
	Public function editPwd () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}

		$db = M('user');
		//验证旧密码
		$where = array('id' => session('uid'));
		$old = $db->where($where)->getField('password');
		
		if ($this->_post('old', 'md5') != $old) {
				$this->error('旧密码错误');
		}

		if ($this->_post('new') != $this->_post('newed')) {
			$this->error('两次密码不一致');
		}

		$newPwd = $this->_post('new', 'md5');
		$data = array(
			'id' => session('uid'),
			'password' => $newPwd
			);

		if ($db->save($data)) {
			$this->success('修改成功', U('index'));
		} else {
			$this->error('修改失败，请重试...');
		}
	}
}
?>