<?php

namespace Zifan\SocialiteDingtalk;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class Provider
 * @author zifan
 * @date 2023/1/30 11:20
 *
 * @link https://open.dingtalk.com/document/orgapp-server/tutorial-obtaining-user-personal-information #实现登录第三方网站（扫码或账密方式）
 * @link https://open.dingtalk.com/document/app/use-dingtalk-account-to-log-on-to-third-party-websites #使用钉钉账号登录第三方网站（旧版）
 * @link https://open.dingtalk.com/document/tutorial/dingtalk-logon-free-third-party-websites          #钉钉内免登第三方网站（不确定是不是旧版）
 * @link https://github.com/alibabacloud-sdk-php/dingtalk #SDK安装地址
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'DINGTALK';

    /**
     * {@inheritdoc}
     * @description
     * - openid：授权后可获得用户userid
     * - openid corpid：授权后可获得用户id和登录过程中用户选择的组织id
     */
    protected $scopes = ['openid', 'corpid']; // snsapi_login

    protected $scopeSeparator = ' ';

    protected $parameters = ['prompt' => 'consent'/*, 'state' => '跟随authCode原样返回', ...*/];

    public function getScopes()
    {
        if (!empty($this->config['scopes'])) {
            $this->scopes = (array)$this->config['scopes'];
        }

        return $this->scopes; // parent::getScopes();
    }

    /**
     * 设置额外配置键
     * @return string[]
     * @description 设置额外配置键时本可以不用指定key name，奈何数组合并源码使用`+`操作符，导致默认额外配置键(guzzle)丢失
     * @see \SocialiteProviders\Manager\Helpers\ConfigRetriever::fromServices()
     */
    public static function additionalConfigKeys()
    {
        return ['scopes' => 'scopes'];
    }

    /**
     * {@inheritdoc}
     * @internal 旧接口：https://oapi.dingtalk.com/connect/oauth2/sns_authorize
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.dingtalk.com/oauth2/auth', $state);
    }

    protected function getCode()
    {
        return $this->request->input('authCode');
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'json' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}.
     * @example 接口返回示例 HTTP/1.1 200 OK
     * Content-Type:application/json
     * {
     *     "accessToken" : "abcd",
     *     "refreshToken" : "abcd",
     *     "expireIn" : 7200,
     *     "corpId" : "corpxxxx" #可能不存在
     * }
     *
     * @internal 两个获取token接口的比较：
     * 针对某个用户授权返回的token
     * -- https://api.dingtalk.com/v1.0/oauth2/userAccessToken
     * 针对某个应用授权返回的token
     * -- https://oapi.dingtalk.com/gettoken
     * @link https://open.dingtalk.com/document/orgapp/obtain-user-token #获取用户access_token
     * @link https://open.dingtalk.com/document/orgapp/obtain-orgapp-token #获取企业内部应用的access_token
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.dingtalk.com/v1.0/oauth2/userAccessToken';
    }

    /**
     * {@inheritdoc}.
     * @internal
     * - refreshToken OAuth2.0刷新令牌，从返回结果里面获取。
     * - grantType 如果使用授权码换token，传authorization_code；如果使用刷新token换用户token，传refresh_token。
     */
    protected function getTokenFields($code): array
    {
        $fields = [];

        foreach (parent::getTokenFields($code) as $key => $value) {
            $fields[Str::camel($key)] = $value;
        }

        return $fields;
    }

    /**
     * Get the access token from the token response body.
     *
     * @param array $body
     *
     * @return string
     */
    protected function parseAccessToken($body): string
    {
        return Arr::get($body, 'accessToken');
    }

    /**
     * Get the refresh token from the token response body.
     *
     * @param array $body
     *
     * @return string
     */
    protected function parseRefreshToken($body): string
    {
        return Arr::get($body, 'refreshToken');
    }

    /**
     * Get the expires in from the token response body.
     *
     * @param array $body
     *
     * @return string
     */
    protected function parseExpiresIn($body): string
    {
        return Arr::get($body, 'expireIn');
    }

    /**
     * {@inheritdoc}.
     * @example 接口返回示例：HTTP/1.1 200 OK
     * Content-Type:application/json
     * {
     *     "nick" : "zhangsan",
     *     "avatarUrl" : "https://xxx",
     *     "mobile" : "150xxxx9144",
     *     "openId" : "123",
     *     "unionId" : "z21HjQliSzpw0Yxxxx",
     *     "email" : "zhangsan@alibaba-inc.com",
     *     "stateCode" : "86" #手机号对应的国家号
     * }
     *
     * @internal 旧接口：https://oapi.dingtalk.com/sns/getuserinfo_bycode
     * @note
     * $response = $this->getHttpClient()->post('https://api.dingtalk.com/v1.0/contact/users/me', [
     *     'query' => [
     *         'accessKey' => $this->clientId,
     *         'timestamp' => $canonicalString = intval(microtime(true) * 1000),
     *         'signature' => base64_encode(hash_hmac('sha256', $canonicalString, $this->clientSecret, true)),
     *     ],
     *     'json' => [ // body: 需要字符串; form_params: 需要数组；json: 数组或字符串
     *         'tmp_auth_code' => $token
     *     ],
     *     'verify' => false
     * ]);
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://api.dingtalk.com/v1.0/contact/users/me', [
            'headers' => ['x-acs-dingtalk-access-token' => $token]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}.
     * @internal `openid` VS `unionid`
     * - openid  同一用户同一应用唯一
     * - unionid 同一用户不同应用唯一
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'openId'),
            'unionid'  => Arr::get($user, 'unionId'),
            'nickname' => Arr::get($user, 'nick'),
            'avatar'   => Arr::get($user, 'avatarUrl'),
            'name'     => Arr::get($user, 'mobile'),
            'email'    => Arr::get($user, 'email'),
        ]);
    }
}
