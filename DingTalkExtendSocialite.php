<?php

namespace Zifan\SocialiteDingtalk;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DingTalkExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dingtalk', Provider::class);
    }
}
