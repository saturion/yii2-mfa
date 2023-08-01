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

/**
 * Class VerifyAction provide an action verify mfa otp. For use, add it to actions method of your controller
 * Example:
 * ```php
 *       public function actions()
 *       {
 *           return [
 *               'verify' => [
 *                   'class' => 'vxm\mfa\VerifyAction',
 *                   'viewFile' => 'verify', // the name of view file use to render view
 *                   'formVar' => 'model', // the name of variable use to parse [[\vxm\mfa\OtpForm]] object to view file.
 *                   'retry' => true, // allow user retry when type wrong otp
 *                   'successCallback' => [$this, 'mfaPassed'], // callable call when user type valid otp if not set [[yii\web\Controller::goBack()]] will be call.
 *                   'invalidCallback' => [$this, 'mfaOtpInvalid'], // callable call when user type wrong otp if not set and property `retry` is false [[yii\web\User::loginRequired()]] will be call, it should be use for set flash notice to user.
 *                   'retry' => true, // allow user retry when type wrong otp
 *               ]
 *           ];
 *       }
 * ```
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class VerifyAction extends Action
{

    use EnsureUserBehaviorAttachedTrait;

    /**
     * @var string the name of view file if not set an id of this action will be use.
     */
    public $viewFile;

    /**
     * @var string the name of variable in view refer to an object of `vxm\mfa\OtpForm`.
     */
    public $formVar = 'model';

    /**
     * @var callable|null when an identity had been verified it will be call. If not set, [[\yii\web\Controller::goBack()]] will be call.
     * This action will be parse at first param and `vxm\mfa\OtpForm` is second param
     * Example:
     *
     * ```php
     * 'successCallback' => function(\vxm\mfa\VerifyAction $action, \vxm\mfa\OtpForm $otp) {
     *
     *      return $action->controller->redirect(['site/dash-board']);
     * }
     *
     * ```
     */
    public $successCallback;

    /**
     * @var callable|null when an user submit wrong otp it will be call, if not set, [[yii\web\User::loginRequired()]] will be call.
     * This action will be parse at first param and `vxm\mfa\OtpForm` is second param
     * Example:
     *
     * ```php
     * 'invalidCallback' => function(\vxm\mfa\VerifyAction $action, \vxm\mfa\OtpForm $otp) {
     *      Yii::$app->session->setFlash('Otp is not valid');
     *
     *      return $action->controller->redirect(['site/login']);
     * }
     *
     * ```
     */
    public $invalidCallback;
    
    /**
     * Note that a prover may send the same OTP inside a given time-step window multiple times to a verifier.
     * The verifier MUST NOT accept the second attempt of the OTP after the successful validation has been issued for the first OTP,
     * which ensures one-time only use of an OTP.
     * 
     */
    public $oneTimeOnlyUseCallback;
    
    public $attemptSuccessCallback;
    public $attemptFailCallback;

    /**
     * @var bool weather allow user can retry when type wrong or not.
     */
    public $retry = false;

    /**
     * @var string the form class handle end-user data
     */
    public $formClass = OtpForm::class;
    
    /**
     * @var string name of the layout for the rendered view
     */
    public $layout = 'main';

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->ensureUserBehaviorAttached();
        $this->viewFile = $this->viewFile ?? $this->id;
        
        $this->controller->layout = $this->layout;

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
        $formClass = $this->formClass;
        $form = new $formClass(['user' => $this->user, 'auth_method' => 1]);
        
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            
            $is_otp_first_use = true;
            if ($this->oneTimeOnlyUseCallback) {
                $is_otp_first_use = call_user_func($this->oneTimeOnlyUseCallback, $this, $form);
            }
            
            if ($form->verify() && $is_otp_first_use) {
                $this->user->switchIdentityLoggedIn();
                $this->user->removeIdentityLoggedIn();
                
                if ($this->attemptSuccessCallback) {
                    call_user_func($this->attemptSuccessCallback, $this, $form);
                }

                if ($this->successCallback) {
                    return call_user_func($this->successCallback, $this, $form);
                } else {
                    return $this->controller->goBack();
                }
            } else {
                
                if ($this->attemptFailCallback && $this->retry) {
                    call_user_func($this->attemptFailCallback, $this, $form);
                }

                if (!$is_otp_first_use) {
                    $form->addError('otp', Yii::t('app', 'Das Einmalpasswort ist ungültig'));
                }
                
                if (!$this->retry) {
                    $this->user->removeIdentityLoggedIn();
                }

                if ($this->invalidCallback) {
                    return call_user_func($this->invalidCallback, $this, $form);
                } elseif (!$this->retry) {
                    return $this->user->loginRequired();
                }
            }
        }
        
        return $this->controller->render($this->viewFile, [$this->formVar => $form]);
    }

}
