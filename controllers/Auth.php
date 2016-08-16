<?php
/**
 * @copyright Ilch 2.0
 */

namespace Modules\Twitterauth\Controllers;

use Modules\Twitterauth\Libs\TwitterAuth;
use Modules\User\Mappers\AuthProvider;
use Modules\User\Mappers\User as UserMapper;
use Modules\User\Mappers\Group;
use Modules\User\Models\AuthProviderUser;
use Modules\User\Models\User;
use Modules\User\Service\Password as PasswordService;
use Ilch\Validation;

class Auth extends \Ilch\Controller\Frontend
{
    public function indexAction()
    {
        if (loggedIn()) {
            $authProvider = new AuthProvider();

            if ($authProvider->hasProviderLinked('twitter', currentUser()->getId())) {
                $this->addMessage('providerAlreadyLinked', 'danger');
                $this->redirect('/');
            }
        }

        $auth = (new TwitterAuth())
            ->setMethod('POST')
            ->setUrl('https://api.twitter.com/oauth/request_token')
            ->setConsumerKey($this->getConfig()->get('twitterauth_consumer_key'))
            ->setConsumerSecret($this->getConfig()->get('twitterauth_consumer_secret'))
            ->setToken($this->getConfig()->get('twitterauth_access_token'))
            ->setTokenSecret($this->getConfig()->get('twitterauth_access_token_secret'))
            ->setCallback($this->getLayout()->getUrl([
                'module' => 'twitterauth',
                'controller' => 'auth',
                'action' => 'callback',
            ]))
            ->exec();

        if (!$auth->hasError()) {
            if ((bool) $auth->getResult()['oauth_callback_confirmed'] !== true) {
                $this->addMessage('unknownErrorOccured', 'danger');
                $this->redirect('/');
            }

            $_SESSION['initial_oauth_token'] = $auth->getResult()['oauth_token'];

            $this->redirect(
                'https://api.twitter.com/oauth/authenticate?oauth_token='.$auth->getResult()['oauth_token']
            );
        }

        echo $auth->getErrors()[0]->code;
        echo $auth->getErrors()[0]->message;
    }

    public function callbackAction()
    {
        $oauthVerifier = $this->getRequest()->getQuery('oauth_verifier');
        $oauthToken = $this->getRequest()->getQuery('oauth_token');

        if (is_null($oauthVerifier) || is_null($oauthToken)) {
            $this->addMessage('badRequest', 'danger');
            $this->redirect('/');
        }

        if (!isset($_SESSION['initial_oauth_token']) ||
            (isset($_SESSION['initial_oauth_token']) && $oauthToken !== $_SESSION['initial_oauth_token'])) {
            unset($_SESSION['initial_oauth_token']);
            $this->addMessage('badRequest', 'danger');
            $this->redirect('/');
        }

        $auth = (new TwitterAuth())
            ->setMethod('POST')
            ->setUrl('https://api.twitter.com/oauth/access_token')
            ->setConsumerKey($this->getConfig()->get('twitterauth_consumer_key'))
            ->setConsumerSecret($this->getConfig()->get('twitterauth_consumer_secret'))
            ->setToken($oauthToken)
            ->setTokenSecret('')
            ->addField('oauth_verifier', $oauthVerifier)
            ->setWithout('oauth_callback')
            ->exec();

        if (!$auth->hasError()) {
            $authProvider = new AuthProvider();
            $data = $auth->getResult();

            $oauthToken = isset($data['oauth_token']) ? $data['oauth_token'] : null;
            $oauthTokenSecret = isset($data['oauth_token_secret']) ? $data['oauth_token_secret'] : null;

            $existingLink = $authProvider->providerAccountIsLinked('twitter', $data['user_id']);

            if (loggedIn()) {
                if ($authProvider->hasProviderLinked('twitter', currentUser()->getId())) {
                    $this->addMessage('providerAlreadyLinked', 'danger');
                    $this->redirect('/');
                }

                if ($existingLink === true) {
                    $this->addMessage('accountAlreadyLinkedToDifferentUser', 'danger');
                    $this->redirect('/');
                }

                $authProviderUser = (new AuthProviderUser())
                    ->setIdentifier($data['user_id'])
                    ->setProvider('twitter')
                    ->setOauthToken($oauthToken)
                    ->setOauthTokenSecret($oauthTokenSecret)
                    ->setScreenName($data['screen_name'])
                    ->setUserId(currentUser()->getId());

                $link = $authProvider->linkProviderWithUser($authProviderUser);

                if ($link === true) {
                    $this->addMessage('linkSuccess');
                    $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                }

                $this->addMessage('linkFailed', 'danger');
                $this->redirect('/');
            }

            if ($existingLink === true) {
                // TODO: Admin panel setting?
                $remember = false;
                $user_id = $authProvider->getUserIdByProvider('twitter', $data['user_id']);

                if (is_null($user_id)) {
                    $this->addMessage('couldNotFindRequestedUser');
                    $this->redirect('/');
                }

                if ($remember === false) {
                    $_SESSION['user_id'] = $user_id;
                }

                $this->addMessage('loginSuccess');
                $this->redirect('/');
            }

            $_SESSION['oauth_login'] = [
                'oauth_token' => $oauthToken,
                'oauth_token_secret' => $oauthTokenSecret,
                'data' => $data,
                'timestamp' => strtotime('+5 minutes'),
            ];

            $this->redirect(['action' => 'regist']);
        }

        $this->addMessage('requestDenied', 'danger');
        $this->redirect(['module' => 'user', 'controller' => 'regist', 'action' => 'index']);
    }

    public function registAction()
    {
        if (!isset($_SESSION['oauth_login']) || $_SESSION['oauth_login']['timestamp'] < time()) {
            $this->addMessage('registExpired', 'danger');
            $this->redirect(['module' => 'user', 'controller' => 'regist', 'action' => 'index']);
        }

        $oauth = $_SESSION['oauth_login'];
        $errors = new \Ilch\Validation\ErrorBag();

        // Pull errors from $_SESSION and unset them
        if (isset($_SESSION['errors'])) {
            $errors->setErrors($_SESSION['errors']);
            unset($_SESSION['errors']);
        }

        $old = array_dot($_SESSION, 'old', []);

        if (isset($_SESSION['old'])) {
            unset($_SESSION['old']);
        }

        $this->getView()->set('rules', $this->getConfig()->get('regist_rules'));
        $this->getView()->set('errors', $errors);
        $this->getView()->set('old', $old);
        $this->getView()->set('user', $oauth['data']);
    }

    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->addMessage('badRequest');
            $this->redirect('/');
        }

        if (!isset($_SESSION['oauth_login']) && $_SESSION['oauth_login']['timestamp'] < time()) {
            $this->addMessage('badRequest');
            $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
        }

        $input = [
            'userName' => trim($this->getRequest()->getPost('userName')),
            'email' => trim($this->getRequest()->getPost('email')),
        ];

        $validation = Validation::create($input, [
            'userName' => 'required|unique,table:users,column:name',
            'email' => 'required|email|unique,table:users,column:email',
        ]);

        if ($validation->isValid()) {
            // register user
            $registMapper = new UserMapper();
            $groupMapper = new Group();
            $userGroup = $groupMapper->getGroupById(2);
            $currentDate = new \Ilch\Date();

            $user = (new User())
                ->setName($input['userName'])
                ->setPassword((new PasswordService())->hash(PasswordService::generateSecurePassword(32)))
                ->setEmail($input['email'])
                ->setDateCreated($currentDate->format('Y-m-d H:i:s', true))
                ->addGroup($userGroup)
                ->setDateConfirmed($currentDate->format('Y-m-d H:i:s', true));

            $userId = $registMapper->save($user);

            $oauth = $_SESSION['oauth_login'];

            $authProviderUser = (new AuthProviderUser())
                ->setIdentifier($oauth['data']['user_id'])
                ->setProvider('twitter')
                ->setOauthToken($oauth['oauth_token'])
                ->setOauthTokenSecret($oauth['oauth_token_secret'])
                ->setScreenName($oauth['data']['screen_name'])
                ->setUserId($userId);

            unset($_SESSION['oauth_login']);

            $link = (new AuthProvider())->linkProviderWithUser($authProviderUser);

            if ($link === true) {
                $_SESSION['user_id'] = $userId;

                $this->addMessage('linkSuccess');
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'index']);
            }

            $this->addMessage('linkFailed', 'danger');
            $this->redirect('/');
        }

        $_SESSION['errors'] = $validation->getErrorBag()->getErrors();
        $_SESSION['old'] = $input;

        $this->redirect(['action' => 'regist']);
    }

    public function unlinkAction()
    {
        if (loggedIn()) {
            if ($this->getRequest()->isPost()) {
                $authProvider = new AuthProvider();
                $res = $authProvider->unlinkUser('twitter', currentUser()->getId());

                if ($res > 0) {
                    $this->addMessage('unlinkedSuccessfully');
                    $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                }

                $this->addMessage('couldNotUnlink', 'danger');
                $this->redirect('/');
            }

            $this->addMessage('badRequest', 'danger');
            $this->redirect('/');
        }

        $this->addMessage('notAuthenticated', 'danger');
        $this->redirect('/');
    }
}
