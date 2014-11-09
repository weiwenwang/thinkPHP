<?php
/**
 * 首页控制器
 */
Class IndexAction extends CommonAction {

	/**
	 * 首页视图
	 */
	Public function index () {

		//实例化微博视图模型
		$db = D('WeiboView');
		import('ORG.Util.Page');

		//取得当前用户的ID与当前用户所有关注好友的ID
		$uid = array(session('uid'));
		$where = array('fans' => session('uid'));

		if (isset($_GET['gid'])) {
			$gid = $this->_get('gid', 'intval');
			$where['gid'] = $gid;
			$uid = array();
		}

		$result = M('follow')->field('follow')->where($where)->select();
		if ($result) {
			foreach ($result as $v) {
				$uid[] = $v['follow'];
			}
		}

		//组合WHERE条件,条件为当前用户自身的ID与当前用户所有关注好友的ID
		$where = array('uid' => array('IN', $uid));

		//统计数据总条数，用于分页
		$count = $db->where($where)->count();
		$page = new Page($count, 20);
		$limit = $page->firstRow . ',' . $page->listRows;

		//读取所有微博
		$result = $db->getAll($where, $limit);

		$this->weibo = $result;
		$this->page = $page->show();
		$this->display();
	}

	/**
	 * 微博发布处理
	 */
	Public function sendWeibo () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}
		$data = array(
			'content' => $this->_post('content'),
			'time' => time(),
			'uid' => session('uid')
			);
		if ($wid = M('weibo')->data($data)->add()) {
			if (!empty($_POST['max'])) {
				$img = array(
					'mini' => $this->_post('mini'),
					'medium' => $this->_post('medium'),
					'max' => $this->_post('max'),
					'wid' => $wid
					);
				M('picture')->data($img)->add();
			}
			M('userinfo')->where(array('uid' => session('uid')))->setInc('weibo');
			$this->success('发布成功', U('index'));
		} else {
			$this->error('发布失败请重试...');
		}
	}

	/**
	 * 转发微博
	 */
	Public function turn () {
		if (!$this->isPost()) {
			halt('页面不存在');
		}
		//原微博ID
		$id = $this->_post('id', 'intval');
		$tid = $this->_post('tid', 'intval');
		//转发内容
		$content = $this->_post('content');

		//提取插入数据
		$data = array(
			'content' => $content,
			'isturn' => $tid ? $tid : $id,
			'time' => time(),
			'uid' => session('uid')
			);
		
		//插入数据至微博表
		$db = M('weibo');
		if ($db->data($data)->add()) {
			//原微博转发数+1
			$db->where(array('id' => $id))->setInc('turn');

			if ($tid) {
				$db->where(array('id' => $tid))->setInc('turn');
			}

			//用户发布微博数+1
			M('userinfo')->where(array('uid' => session('uid')))->setInc('weibo');

			//如果点击了同时评论插入内容到评论表
			if (isset($_POST['becomment'])) {
				$data = array(
					'content' => $content,
					'time' => time(),
					'uid' => session('uid'),
					'wid' => $id
					);
				//插入评论数据后给原微博评论次数+1
				if (M('comment')->data($data)->add()) {
					$db->where(array('id' => $id))->setInc('comment');
				}
			}

			$this->success('转发成功', U('index'));
		} else {
			$this->error('转发失败请重试...');
		}
	}

	/**
	 * 评论
	 */
	Public function comment () {
		if (!$this->isAjax()) {
			halt('页面不存在');
		}
		//提取评论数据
		$data = array(
			'content' => $this->_post('content'),
			'time' => time(),
			'uid' => session('uid'),
			'wid' => $this->_post('wid', 'intval')
			);

		if (M('comment')->data($data)->add()) {
			//读取评论用户信息
			$field = array('username', 'face50' => 'face', 'uid');
			$where = array('uid' => $data['uid']);
			$user = M('userinfo')->where($where)->field($field)->find();

			//被评论微博的发布者用户名
			$uid = $this->_post('uid', 'intval');
			$username = M('userinfo')->where(array('uid' => $uid))->getField('username');

			$db = M('weibo');
			//评论数+1
			$db->where(array('id' => $data['wid']))->setInc('comment');

			//评论同时转发时处理
			if ($_POST['isturn']) {
				//读取转发微博ID与内容
				$field = array('id', 'content', 'isturn');
				$weibo = $db->field($field)->find($data['wid']);
				$content = $weibo['isturn'] ? $data['content'] . ' // @' . $username . ' : ' . $weibo['content'] : $data['content'];

				//同时转发到微博的数据
				$cons = array(
					'content' => $content,
					'isturn' => $weibo['isturn'] ? $weibo['isturn'] : $data['wid'],
					'time' => $data['time'],
					'uid' => $data['uid']
					);

				if ($db->data($cons)->add()) {
					$db->where(array('id' => $weibo['id']))->setInc('turn');
				}

				echo 1;
				exit();
			}

			//组合评论样式字符串返回
			$str = '';
			$str .= '<dl class="comment_content">';
			$str .= '<dt><a href="' . U('/' . $data['uid']) . '">';
			$str .= '<img src="';
			$str .= __ROOT__;
			if ($user['face']) {
				$str .= '/Uploads/Face/' . $user['face'];
			} else {
				$str .= '/Public/Images/noface.gif';
			}
			$str .= '" alt="' . $user['username'] . '" width="30" height="30"/>';
	        $str .= '</a></dt><dd>';  
	        $str .= '<a href="' . U('/' . $data['uid']) . '" class="comment_name">';
	        $str .= $user['username'] . '</a> : ' . replace_weibo($data['content']);
	        $str .= '&nbsp;&nbsp;( ' . time_format($data['time']) . ' )';
	        $str .= '<div class="reply">';
	        $str .= '<a href="">回复</a>';
			$str .= '</div></dd></dl>';
			echo $str;

		} else {
			echo 'false';
		}
		
	}

	/**
	 * 异步获取评论内容
	 */
	Public function getComment () {
		if (!$this->isAjax()) {
			halt('页面不存在');
		}
		$wid = $this->_post('wid', 'intval');
		$where = array('wid' => $wid);

		//数据的总条数
		$count = M('comment')->where($where)->count();
		//数据可分的总页数
		$total = ceil($count / 10);
		$page = isset($_POST['page']) ? $this->_post('page', 'intval') : 1;
		$limit = $page < 2 ? '0,10' : (10 * ($page - 1)) . ',10';

		$result = D('CommentView')->where($where)->order('time DESC')->limit($limit)->select();

		if ($result) {
			$str = '';
			foreach ($result as $v) {
				$str .= '<dl class="comment_content">';
				$str .= '<dt><a href="' . U('/' . $v['uid']) . '">';
				$str .= '<img src="';
				$str .= __ROOT__;
				if ($v['face']) {
					$str .= '/Uploads/Face/' . $v['face'];
				} else {
					$str .= '/Public/Images/noface.gif';
				}
				$str .= '" alt="' . $v['username'] . '" width="30" height="30"/>';
		        $str .= '</a></dt><dd>';  
		        $str .= '<a href="' . U('/' . $v['uid']) . '" class="comment_name">';
		        $str .= $v['username'] . '</a> : ' . replace_weibo($v['content']);
		        $str .= '&nbsp;&nbsp;( ' . time_format($v['time']) . ' )';
		        $str .= '<div class="reply">';
		        $str .= '<a href="">回复</a>';
				$str .= '</div></dd></dl>';
			}

			if ($total > 1) {
				$str .= '<dl class="comment-page">';

				switch ($page) {
					case $page > 1 && $page < $total :
						$str .= '<dd page="' . ($page - 1) . '" wid="' . $wid . '">上一页</dd>';
						$str .= '<dd page="' . ($page + 1) . '" wid="' . $wid . '">下一页</dd>';
						break;

					case $page < $total : 
						$str .= '<dd page="' . ($page + 1) . '" wid="' . $wid . '">下一页</dd>';
						break;

					case $page == $total : 
						$str .= '<dd page="' . ($page - 1) . '" wid="' . $wid . '">上一页</dd>';
						break;
				}

				$str .= '</dl>';
			}

			echo $str;

		} else {
			echo 'false';
		}
	}

	/**
	 * 退出登录处理
	 */
	Public function loginOut () {
		//卸载SESSION
		session_unset();
		session_destroy();

		//删除用于自动登录的COOKIE
		@setcookie('auto', '', time() - 3600, '/');

		//跳转致登录页
		redirect(U('Login/index'));
	}
}
?>