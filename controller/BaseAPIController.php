<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\login\route\Permission;
use nova\plugin\tpl\Pjax;

/**
 * 登录模块基础控制器
 *
 * 提供用户登录状态检查和权限验证功能
 */
class BaseAPIController extends Controller
{
    /**
     * 当前登录用户
     *
     * @var UserModel|null
     */
    protected ?UserModel $userModel = null;

    /**
     * 初始化控制器
     *
     * 检查用户登录状态和权限，未登录或无权限则重定向
     *
     * @return Response|null 返回重定向响应或null
     */
    public function init(): ?Response
    {
        $this->userModel = LoginManager::getInstance()->checkLogin();
        if ($this->userModel === null) {
            $uri = LoginManager::getInstance()->redirectLogin();
            return Pjax::redirectTo($uri);
        }

        if (!Permission::getInstance()->hasPermission($this->userModel)) {
            return Pjax::responseErrorJson(403);
        }

        return null;
    }
}
