<?php
/**
 * 用户个人页控制器
 */
Class UserAction extends CommonAction {

	/**
	 * 用户个人页视图
	 */
	Public function index () {
		$id = $this->_get('id', 'intval');
		echo $id;
	}

	/**
	 * 空操作
	 */
	Public function _empty ($name) {
		$this->_getUrl($name);
	}

	/**
	 * 处理用户名空操作，获得用户ID 跳转至用户个人页
	 */
	Private function _getUrl ($name) {
		$name = htmlspecialchars($name);
		$where = array('username' => $name);
		$uid = M('userinfo')->where($where)->getField('uid');

		if (!$uid) {
			redirect(U('Index/index'));
		} else {
			redirect(U('/' . $uid));
		}
	}
}
?>