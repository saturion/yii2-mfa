<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use Yii;

use yii\base\Action;
use yii\base\InvalidConfigException;

class SendNotificationEmailAction extends Action
{
    use EnsureUserBehaviorAttachedTrait;

    /**
     * @var callable|null before rendering form view, if not set, [[yii\web\User::loginRequired()]] will be call.
     */
    public $sendNotificationCallback;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->ensureUserBehaviorAttached();

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function beforeRun()
    {
        $data = $this->user->getIdentityLoggedIn();

        if ($data === null) {
            $this->user->loginRequired();

            return false;
        }

        return parent::beforeRun();
    }

    /**
     * @return mixed|string|\yii\web\Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function run()
    {
        return call_user_func($this->sendNotificationCallback);
    }

}
