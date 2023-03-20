# socialiteproviders-dingtalk
基于socialiteproviders/manager的钉钉第三方登录

```bash
composer require zifan/socialite-dingtalk
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'dingtalk' => [
	'client_id' => env('DINGTALK_CLIENT_ID'),
	'client_secret' => env('DINGTALK_CLIENT_SECRET'),
	'redirect' => env('DINGTALK_REDIRECT_URI'),
	// 'guzzle' => ['verify' => false],
	// 'scopes' => ['openid'] #openid：授权后可获得用户userid（一般适用于企业内部应用）；openid corpid：授权后可获得用户id和登录过程中用户选择的组织id
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'Zifan\\SocialiteDingtalk\\DingTalkExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('dingtalk')->redirect();
```

### Returned User fields

- ``openId``
- ``unionid``
- ``nick``
- ``avatarUrl``
- ``mobile``
- ``email``
- ``stateCode``