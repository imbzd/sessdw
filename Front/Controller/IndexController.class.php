<?php
/**
 * 系统入口逻辑控制
 * buzhidao
 * 2015-12-08
 */
namespace Front\Controller;

use Any\Controller;

class IndexController extends BaseController
{
    //导航栏目navflag标识
    public $navflag = 'Index';

    public function __construct()
    {
        parent::__construct();
    }

    //系统首页
    public function index()
    {
        //党建新闻
        $djarclist = D('Article')->getArc(null, CR('Article')->arcclass['news']['id'], null, 0, 7);
        $this->assign('djarclist', $djarclist['data']);

        //平台公告
        $ntarclist = D('Article')->getArc(null, CR('Article')->arcclass['notice']['id'], null, 0, 7);
        $this->assign('ntarclist', $ntarclist['data']);

    	$this->display();
    }

    //获取新闻
    private function _getArc($classid=null)
    {
        $where = array(
            'classid' => $classid,
            'status' => 1,
        );
        $datalist = M('article')->where($where)->order('createtime desc')->limit(0,7)->select();

        return $datalist;
    }
}