<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use Yii;

use yii\base\Model;

/**
 * Class OtpForm
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class OtpForm extends Model
{

    use EnsureUserBehaviorAttachedTrait;

    /**
     * @var string an otp submit from end user
     */
    public $otp;
    
    /**
     * @var int The window of TOTP acts as time drift.
     */
    public $window = null;
    
    /**
     * @var int|null $auth_method  integer representing the current auth method in use
     * 
     *  values: METODO_AUTH_APP = 1, METODO_EMAIL = 2;
     */
    public $auth_method = NULL;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->ensureUserBehaviorAttached();

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['otp'], 'required']
        ];
    }

    /**
     * Verify an otp is valid with current logged in user
     *
     * @return bool weather an otp property is valid.
     */
    public function verify()
    {
        if (!$this->user->validateOtpByIdentityLoggedIn($this->otp, $this->window, $this->auth_method)) {
            $this->addError('otp', Yii::t('app', 'Das Einmalpasswort ist ungültig oder abgelaufen'));

            return false;
        } else {
            return true;
        }
    }


}
