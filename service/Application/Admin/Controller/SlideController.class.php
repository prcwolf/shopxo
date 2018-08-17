<?php

namespace Admin\Controller;

/**
 * 轮播图片管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class SlideController extends CommonController
{
    /**
     * [_initialize 前置操作-继承公共前置方法]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function _initialize()
    {
        // 调用父类前置方法
        parent::_initialize();

        // 登录校验
        $this->Is_Login();

        // 权限校验
        $this->Is_Power();
    }

    /**
     * [Index 轮播图片列表]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     */
    public function Index()
    {
        // 参数
        $param = array_merge($_POST, $_GET);

        // 模型对象
        $m = M('Slide');

        // 条件
        $where = $this->GetIndexWhere();

        // 分页
        $number = MyC('admin_page_number');
        $page_param = array(
                'number'    =>  $number,
                'total'     =>  $m->where($where)->count(),
                'where'     =>  $param,
                'url'       =>  U('Admin/Slide/Index'),
            );
        $page = new \Library\Page($page_param);

        // 获取列表
        $list = $this->SetDataHandle($m->where($where)->limit($page->GetPageStarNumber(), $number)->order('is_enable desc, id desc')->select());

        // 参数
        $this->assign('param', $param);

        // 分页
        $this->assign('page_html', $page->GetPageHtml());

        // 是否启用
        $this->assign('common_is_enable_list', L('common_is_enable_list'));

        // 所属平台
        $this->assign('common_platform_type', L('common_platform_type'));

        // 跳转类型
        $this->assign('common_jump_url_type', L('common_jump_url_type'));

        // 数据列表
        $this->assign('list', $list);
        $this->display('Index');
    }

    /**
     * [SetDataHandle 数据处理]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-29T21:27:15+0800
     * @param    [array]      $data [轮播图片数据]
     * @return   [array]            [处理好的数据]
     */
    private function SetDataHandle($data)
    {
        if(!empty($data))
        {
            $common_platform_type = L('common_platform_type');
            $common_is_enable_tips = L('common_is_enable_tips');
            $common_jump_url_type = L('common_jump_url_type');
            foreach($data as &$v)
            {
                // 是否启用
                $v['is_enable_text'] = $common_is_enable_tips[$v['is_enable']]['name'];

                // 平台类型
                $v['platform_text'] = $common_platform_type[$v['platform']]['name'];

                // 跳转类型
                $v['jump_url_type_text'] = $common_jump_url_type[$v['jump_url_type']]['name'];

                // 图片地址
                $v['images_url'] =  empty($v['images_url']) ? '' : C('IMAGE_HOST').$v['images_url'];

                // 添加时间
                $v['add_time_text'] = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time_text'] = date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return $data;
    }

    /**
     * [GetIndexWhere 列表条件]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     */
    private function GetIndexWhere()
    {
        $where = array();

        // 模糊
        if(!empty($_REQUEST['keyword']))
        {
            $where['name'] = array('like', '%'.I('keyword').'%');
        }

        // 是否更多条件
        if(I('is_more', 0) == 1)
        {
            if(I('is_enable', -1) > -1)
            {
                $where['is_enable'] = intval(I('is_enable', 0));
            }
            if(I('jump_url_type', -1) > -1)
            {
                $where['jump_url_type'] = intval(I('jump_url_type', 0));
            }
            if(!empty($_REQUEST['platform']))
            {
                $where['platform'] = I('platform');
            }

            // 表达式
            if(!empty($_REQUEST['time_start']))
            {
                $where['add_time'][] = array('gt', strtotime(I('time_start')));
            }
            if(!empty($_REQUEST['time_end']))
            {
                $where['add_time'][] = array('lt', strtotime(I('time_end')));
            }
        }
        return $where;
    }

    /**
     * [SaveInfo 添加/编辑页面]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-14T21:37:02+0800
     */
    public function SaveInfo()
    {
        // 轮播图片信息
        $data = empty($_REQUEST['id']) ? array() : M('Slide')->find(I('id'));
        $this->assign('data', $data);

        // 是否启用
        $this->assign('common_is_enable_list', L('common_is_enable_list'));

        // 所属平台
        $this->assign('common_platform_type', L('common_platform_type'));

        // 跳转类型
        $this->assign('common_jump_url_type', L('common_jump_url_type'));

        // 参数
        $this->assign('param', array_merge($_POST, $_GET));

        $this->display('SaveInfo');
    }

    /**
     * [Save 轮播图片添加/编辑]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-14T21:37:02+0800
     */
    public function Save()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error(L('common_unauthorized_access'));
        }

        // 图片
        if(!empty($_FILES['file_images_url']))
        {
            // 文件上传校验
            $error = FileUploadError('file_images_url');
            if($error !== true)
            {
                $this->ajaxReturn($error, -1);
            }

            // 文件类型
            list($type, $suffix) = explode('/', $_FILES['file_images_url']['type']);
            $path = 'Public'.DS.'Upload'.DS.'slide'.DS.date('Y').DS.date('m').DS;
            if(!is_dir($path))
            {
                mkdir(ROOT_PATH.$path, 0777, true);
            }
            $filename = date('YmdHis').GetNumberCode(6).'.'.$suffix;
            $file_images_url = $path.$filename;

            if(move_uploaded_file($_FILES['file_images_url']['tmp_name'], ROOT_PATH.$file_images_url))
            {
                $_POST['images_url'] = DS.$file_images_url;
            }
        }

        // 添加
        if(empty($_POST['id']))
        {
            $this->Add();

        // 编辑
        } else {
            $this->Edit();
        }
    }

    /**
     * [Add 轮播图片添加]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-18T16:20:59+0800
     */
    private function Add()
    {
        // 轮播图片模型
        $m = D('Slide');

        // 数据自动校验
        if($m->create($_POST, 1))
        {
            // 额外数据处理
            $m->name            =   I('name');
            $m->jump_url        =   I('jump_url');
            $m->jump_url_type   =   intval(I('jump_url_type'));
            $m->images_url      =   I('images_url');
            $m->platform        =   I('platform');
            $m->is_enable       =   intval(I('is_enable'));
            $m->bg_color        =   I('bg_color');
            $m->add_time        =   time();

            // 数据添加
            if($m->add())
            {
                $this->ajaxReturn(L('common_operation_add_success'));
            } else {
                $this->ajaxReturn(L('common_operation_add_error'), -100);
            }
        } else {
            $this->ajaxReturn($m->getError(), -1);
        }
    }

    /**
     * [Edit 轮播图片编辑]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-17T22:13:40+0800
     */
    private function Edit()
    {
        // 轮播图片模型
        $m = D('Slide');

        // 数据自动校验
        if($m->create($_POST, 2))
        {
            // 额外数据处理
            $m->name            =   I('name');
            $m->jump_url        =   I('jump_url');
            $m->jump_url_type   =   intval(I('jump_url_type'));
            $m->images_url      =   I('images_url');
            $m->platform        =   I('platform');
            $m->is_enable       =   intval(I('is_enable'));
            $m->bg_color        =   I('bg_color');
            $m->upd_time        =   time();

            // 更新数据库
            if($m->where(array('id'=>I('id')))->save())
            {
                $this->ajaxReturn(L('common_operation_edit_success'));
            } else {
                $this->ajaxReturn(L('common_operation_edit_error'), -100);
            }
        } else {
            $this->ajaxReturn($m->getError(), -1);
        }
    }

    /**
     * [Delete 轮播图片删除]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     */
    public function Delete()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error(L('common_unauthorized_access'));
        }

        // 参数处理
        $id = I('id');

        // 删除数据
        if(!empty($id))
        {
            // 模型
            $m = M('Slide');

            // 是否存在
            $data = $m->find($id);
            if(empty($data))
            {
                $this->ajaxReturn(L('common_data_no_exist_error'), -2);
            }
            if($data['is_enable'] == 1)
            {
                $this->ajaxReturn(L('common_already_is_enable_error'), -3);
            }

            // 删除
            if($m->where(array('id'=>$id))->delete() !== false)
            {
                $this->ajaxReturn(L('common_operation_delete_success'));
            } else {
                $this->ajaxReturn(L('common_operation_delete_error'), -100);
            }
        } else {
            $this->ajaxReturn(L('common_param_error'), -1);
        }
    }

    /**
     * [StateUpdate 状态更新]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     */
    public function StateUpdate()
    {
        // 参数
        if(empty($_POST['id']) || !isset($_POST['state']))
        {
            $this->ajaxReturn(L('common_param_error'), -1);
        }

        // 数据更新
        if(M('Slide')->where(array('id'=>I('id')))->save(array('is_enable'=>I('state'))))
        {
            $this->ajaxReturn(L('common_operation_edit_success'));
        } else {
            $this->ajaxReturn(L('common_operation_edit_error'), -100);
        }
    }
}
?>