<?php
/**
 * 读取微博视图模型
 */
Class WeiboViewModel extends ViewModel {

	//定义视图表关联关系
	Protected $viewFields = array(
		'weibo' => array(
			'id', 'content', 'isturn', 'time', 'turn', 'keep', 'comment', 'uid',
			'_type' => 'LEFT'
			),
		'userinfo' => array(
			'username', 'face50' => 'face',
			'_on' => 'weibo.uid = userinfo.uid',
			'_type' => 'LEFT'
			),
		'picture' => array(
			'mini', 'medium', 'max',
			'_on' => 'weibo.id = picture.wid'
			)
		);

	/**
	 * 返回查询返有记录
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	Public function getAll ($where, $limit) {
		$result = $this->where($where)->order('time DESC')->limit($limit)->select();

		//重组结果集数组，得到转发微博
		if ($result) {
			foreach ($result as $k => $v) {
				if ($v['isturn']) {
					$result[$k]['isturn'] = $this->find($v['isturn']);
				}
			}
		}
		return $result;
	}

}
?>