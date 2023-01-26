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
        if (!$this->user->validateOtpByIdentityLoggedIn($this->otp, $this->window)) {
            $this->addError('otp', Yii::t('app', 'El Código ingresado no es válido o expiró'));

            return false;
        } else {
            return true;
        }
    }


}
