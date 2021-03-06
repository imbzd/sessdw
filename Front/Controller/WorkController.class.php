<?php
/**
 * 作业模型逻辑控制
 * buzhidao
 * 2015-12-08
 */
namespace Front\Controller;

use Any\Upload;

class WorkController extends CommonController
{
    //导航栏目navflag标识
    public $navflag = 'Work';

    public function __construct()
    {
        parent::__construct();

        $this->_work_class = C('USER.work_class');
        $this->assign('workclass', $this->_work_class);

        $this->_work_type = C('USER.work_type');
        $this->assign('worktype', $this->_work_type);

        //获取用户作业完成情况
        $this->userworkinfo = D('User')->getUserWorkDone($this->userinfo['userid'], $this->_work_class);
        $this->assign('userworkinfo', $this->userworkinfo);
    }

    //获取分类Id
    private function _getClassid()
    {
        $classid = mRequest('classid');

        return $classid;
    }

    //获取作业id
    private function _getWorkid()
    {
        $workid = mRequest('workid');

        return $workid;
    }

    //作业首页
    public function index()
    {
        $classid = $this->_getClassid();
        $classid = !$classid ? 1 : $classid;
        $this->assign('classid', $classid);

        $userid = $this->userinfo['userid'];

        //检查作业完成情况
        D('User')->ckUserCourseWork($userid);

        list($start, $length) = $this->_mkPage();
        $data = D('Work')->getWork(null, $classid, $userid, $start, $length);
        $total = $data['total'];
        $worklist = $data['data'];

        $count = count($worklist);
        $i = 0;
        foreach ($worklist as $key=>$d) {
            $worklist[$key]['index'] = $count-$i;
            $i++;
        }
        $this->assign('worklist', $worklist);

        $param = array(
            'classid' => $classid,
        );
        $this->assign('param', $param);
        //解析分页数据
        $this->_mkPagination($total, $param);

        $this->assign('APP_PATH', APP_PATH);
        $this->display();
    }

    //上传文件 - 作业报告
    public function workfileupload()
    {
        //初始化上传类
        $Upload = new Upload();
        $Upload->maxSize  = 2097152; //2M
        $Upload->exts     = array('doc', 'docx');
        $Upload->rootPath = UPLOAD_PATH;
        $Upload->savePath = 'file/workfile/';
        $Upload->saveName = array('uniqid', array('', true));
        $Upload->autoSub  = true;
        $Upload->subName  = array('date', 'Ym');

        //上传
        $error = null;
        $msg = '报告提交成功！';
        $data = array();
        $info = $Upload->upload();
        if (!$info) {
            $error = 1;
            $msg = $Upload->getError();
        } else {
            $workid = mRequest('workid');
            if (!$workid) $this->ajaxReturn(1, '请选择作业！');

            $fileinfo = array_shift($info);
            $data = array(
                'userid' => $this->userinfo['userid'],
                'workid' => $workid,
                'savepath' => '/'.UPLOAD_PT.$fileinfo['savepath'],
                'savename' => $fileinfo['savename'],
                'filename' => $fileinfo['name'],
                'filesize' => $fileinfo['size'],
                'ext' => $fileinfo['ext'],
                'createtime' => TIMESTAMP,
            );

            //开始事务
            M('user_work')->startTrans();
            $userworkid = M('user_work')->add(array(
                'userid' => $this->userinfo['userid'],
                'workid' => $workid,
                'status' => 1,
                'createtime' => TIMESTAMP,
            ));
            $fileid = M('user_work_file')->add($data);
            if ($userworkid && $fileid) {
                M('user_work')->commit();
            } else {
                M('user_work')->rollback();
                $error = 1;
                $msg = '报告提交失败！请重新提交！';
            }
        }

        $this->ajaxReturn($error, $msg, $data);
    }

    //完成作业
    public function complete()
    {
        $index = mRequest('index');
        $this->assign('index', $index);

        $userid = $this->userinfo['userid'];

        $workid = mRequest('workid');
        $workinfo = D('Work')->getWorkByID($workid, $userid);
        if (!is_array($workinfo) || empty($workinfo)) {
            header('location:'.__APP__.'?s=Work/index&classid=1');
            exit;
        }

        $workc = mRequest('workc');
        if ($workc && IS_AJAX) {
            $ucontent = mRequest('ucontent');
            if (!$ucontent) $this->ajaxReturn(1,'请填写作业报告！');

            $error = 0;
            $msg = '作业报告提交成功！';
            $data = array(
                'userid' => $userid,
                'workid' => $workid,
                'savepath' => '/'.UPLOAD_PT.'file/workfile/',
                'savename' => 'work.txt',
                'filename' => 'work.txt',
                'filesize' => 1,
                'ext' => 'txt',
                'ucontent' => $ucontent,
                'createtime' => TIMESTAMP,
            );

            //开始事务
            M('user_work')->startTrans();
            $userworkid = M('user_work')->add(array(
                'userid' => $userid,
                'workid' => $workid,
                'status' => 1,
                'completetime' => TIMESTAMP,
            ));
            $fileid = M('user_work_file')->add($data);
            if ($userworkid && $fileid) {
                M('user_work')->commit();
            } else {
                M('user_work')->rollback();
                $error = 1;
                $msg = '作业报告提交失败！请重新提交！';
            }
            $this->ajaxReturn($error, $msg, $data);
        }

        $this->assign('classid', $workinfo['classid']);
        $this->assign('workinfo', $workinfo);
        $this->display();
    }

    //查看作业
    public function profile()
    {
        $index = mRequest('index');
        $this->assign('index', $index);

        $userid = $this->userinfo['userid'];

        $workid = mRequest('workid');
        $workinfo = D('Work')->getWorkByID($workid, $userid);
        if (!is_array($workinfo) || empty($workinfo)) {
            header('location:'.__APP__.'?s=Work/index&classid=1');
            exit;
        }

        $this->assign('classid', $workinfo['classid']);
        $this->assign('workinfo', $workinfo);
        $this->display();
    }

    //作业下载
    public function workfile()
    {
        $userid = $this->userinfo['userid'];

        $workid = mRequest('workid');
        $workinfo = D('Work')->getWorkByID($workid, $userid);
        if (!is_array($workinfo) || empty($workinfo)) {
            header('location:'.__APP__.'?s=Work/index&classid=1');
            exit;
        }

        $workfile = APP_PATH.$workinfo['savepath'].$workinfo['savename'];
        $fileinfo = pathinfo($workfile);
        $filename = $fileinfo['basename'];

        $fp = fopen($workfile, "r");
        $size = filesize($workfile);

        //输入文件标签
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . $size);
        Header("Content-Disposition: attachment; filename=" . $workinfo['filename']);
        //输出文件内容
        //读取文件内容并直接输出到浏览器
        echo fread($fp, $size);
        fclose($fp);
        exit();
    }
}