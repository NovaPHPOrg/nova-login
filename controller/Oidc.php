<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\http\Response;
use nova\plugin\login\LoginConfig;

/**
 * OIDC/SSO 配置控制器
 *
 * 处理 OIDC/SAML 等单点登录配置
 */
class Oidc extends BaseController
{
    /**
     * 获取 OIDC 配置
     *
     * @return Response 返回OIDC配置JSON响应
     */
    public function config(): Response
    {
        return Response::asJson([
            'code' => 200,
            'data' => LoginConfig::getInstance(),
        ]);
    }

    /**
     * 保存 OIDC 配置
     *
     * @return Response 返回操作结果JSON响应
     */
    public function save(): Response
    {
        $post = $this->request->post();
        $config = LoginConfig::getInstance();

        $fields = [
            'ssoEnable', 'ssoMustHasAccount', 'ssoProviderUrl', 'ssoClientId',
            'ssoClientSecret', 'ssoUserField', 'ssoUserInfoUrl', 'ssoDisplayNameField',
            'ssoAvatarField', 'allowedLoginCount', 'loginCallback', 'systemName'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                $config->$field = $post[$field];
            }
        }

        return Response::asJson(['code' => 200, 'msg' => '配置保存成功'], 200);
    }
}
