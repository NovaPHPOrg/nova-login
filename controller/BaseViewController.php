<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use app\Application;

use nova\framework\core\Logger;

use nova\framework\event\EventManager;
use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\login\route\Permission;
use nova\plugin\tpl\Pjax;
use nova\plugin\tpl\ViewResponse;

abstract class BaseViewController extends Controller
{
    // 当前登录用户模型
    protected ?UserModel $userModel = null;

    protected ViewResponse $viewResponse;
    /**
     * 初始化方法，进行域名和登录校验
     * @return Response|null
     */
    public function init(): ?Response
    {
        $this->userModel = LoginManager::getInstance()->checkLogin();

        if (empty($this->userModel)) {
            Logger::info('Access denied: not logged in', ['uri' => $_SERVER['REQUEST_URI'] ?? 'unknown']);
            // 获取登录跳转地址
            $uri = LoginManager::getInstance()->redirectLogin();
            // 如果是 UI 侧控制器，使用 redirectTo 方法跳转
            return Pjax::redirectTo($uri);
        }

        Logger::debug('Access check', ['userId' => $this->userModel->id, 'username' => $this->userModel->username]);

        if (!Permission::getInstance()->hasPermission($this->userModel)) {
            Logger::warning('Access denied: no permission', [
                'userId' => $this->userModel->id,
                'username' => $this->userModel->username,
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);
            return Pjax::responseError(403);
        }

        $this->viewResponse = new ViewResponse();

        $this->viewResponse->init(
            '',
            [
                'title' => Application::SYSTEM_NAME,
                'user' => $this->userModel,
            ]
        );

        if (!$this->request->isPjax()) {

            $menu = $this->getMenu();

            EventManager::trigger('admin.menu', $menu);

            $menu = Permission::getInstance()->filterMenu($menu, $this->userModel);

            Logger::debug('Permission filter result', [
                'userId' => $this->userModel->id,
                'username' => $this->userModel->username,
                'menuItems' => count($menu),
            ]);

            return $this->viewResponse->asTpl("layout", [
                'menuConfig' => $menu
            ]);
        }
        // 调用父类的初始化方法
        return null;
    }

    abstract protected function getMenu(): array;
}
