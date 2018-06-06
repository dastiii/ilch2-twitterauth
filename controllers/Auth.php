<?php

namespace Modules\Twitterauth\Controllers;

use Ilch\Controller\Frontend;
use Modules\Twitterauth\Libs\TwitterOAuth;
use Modules\Twitterauth\Mappers\DbLog;
use Modules\User\Mappers\AuthProvider;
use Modules\User\Mappers\User as UserMapper;
use Modules\User\Mappers\Group;
use Modules\User\Models\AuthProviderUser;
use Modules\User\Models\User;
use Modules\User\Service\Password as PasswordService;
use Ilch\Validation;

class Auth extends Frontend
{
    /**
     * @var DbLog instance
     */
    protected $dbLog;

    /**
     * Renders the register form.
     */
    public function registAction()
    {
        if (! array_dot($_SESSION, 'twitterauth.login') || array_dot($_SESSION, 'twitterauth.login.expires') < time()) {
            $this->addMessage('registExpired', 'danger');
            $this->redirect(['module' => 'user', 'controller' => 'regist', 'action' => 'index']);
        }

        $oauth = array_dot($_SESSION, 'twitterauth.login');

        $this->getView()->set('rules', $this->getConfig()->get('regist_rules'));
        $this->getView()->set('user', $oauth);
    }

    /**
     * Saves the new user to the database.
     */
    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->addMessage('badRequest');
            $this->redirect('/');
        }

        if (! array_dot($_SESSION, 'twitterauth.login') || array_dot($_SESSION, 'twitterauth.login.expires') < time()) {
            $this->addMessage('badRequest');
            $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
        }

        $input = [
            'userName' => trim($this->getRequest()->getPost('userName')),
            'email' => trim($this->getRequest()->getPost('email')),
        ];

        $validation = Validation::create($input, [
            'userName' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
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

            $oauth = array_dot($_SESSION, 'twitterauth.login');

            $authProviderUser = (new AuthProviderUser())
                ->setIdentifier($oauth['user_id'])
                ->setProvider('twitter')
                ->setOauthToken($oauth['oauth_token'])
                ->setOauthTokenSecret($oauth['oauth_token_secret'])
                ->setScreenName($oauth['screen_name'])
                ->setUserId($userId);

            $link = (new AuthProvider())->linkProviderWithUser($authProviderUser);

            if ($link === true) {
                $_SESSION['user_id'] = $userId;

                $this->addMessage('twitterauth.linksuccess');
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'index']);
            }

            $this->addMessage('twitterauth.linkfailed', 'danger');
            $this->redirect('/');
        }

        $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
        $this->redirect()
            ->withInput()
            ->withErrors($validation->getErrorBag())
            ->to(['action' => 'regist']);
    }

    public function unlinkAction()
    {
        if (loggedIn()) {
            if ($this->getRequest()->isPost()) {
                $authProvider = new AuthProvider();
                $res = $authProvider->unlinkUser('twitter', currentUser()->getId());

                if ($res > 0) {
                    $this->addMessage('twitterauth.unlinkedsuccessfully');
                    $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                }

                $this->addMessage('twitterauth.couldnotunlink', 'danger');
                $this->redirect('/');
            }

            $this->addMessage('twitterauth.badrequest', 'danger');
            $this->redirect('/');
        }

        $this->addMessage('twitterauth.notauthenticated', 'danger');
        $this->redirect('/');
    }

    /**
     * Initialize authentication.
     */
    public function indexAction()
    {
        $callbackUrl = $this->getLayout()->getUrl([
            'module' => 'twitterauth',
            'controller' => 'auth',
            'action' => 'callback',
        ]);

        $auth = new TwitterOAuth(
            $this->getConfig()->get('twitterauth_consumer_key'),
            $this->getConfig()->get('twitterauth_consumer_secret'),
            $this->getConfig()->get('twitterauth_access_token'),
            $this->getConfig()->get('twitterauth_access_token_secret'),
            $callbackUrl
        );

        try {
            $auth->obtainTokens();

            $this->redirect($auth->getAuthenticationEndpoint());
        } catch (\Exception $e) {
            $this->addMessage('twitterauth.authenticationfailure', 'danger');

            if (loggedIn()) {
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
            }

            $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
        }
    }

    /**
     * Callback action.
     */
    public function callbackAction()
    {
        $auth = new TwitterOAuth(
            $this->getConfig()->get('twitterauth_consumer_key'),
            $this->getConfig()->get('twitterauth_consumer_secret')
        );

        try {
            $auth->handleCallback($this->getRequest());
            $auth->convertTokens();

            $twitterUser = $auth->getResult();

            $authProvider = new AuthProvider();
            $existingLink = $authProvider->providerAccountIsLinked('twitter', $twitterUser['user_id']);

            if (loggedIn()) {
                if ($authProvider->hasProviderLinked('twitter', currentUser()->getId())) {
                    $this->dbLog()->info(
                        "User " . currentUser()->getName() . " had provider already linked.",
                        [
                            'userId' => currentUser()->getId(),
                            'userName' => currentUser()->getName(),
                            'twitterAccount' => $twitterUser
                        ]
                    );

                    $this->addMessage('providerAlreadyLinked', 'danger');
                    $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                }

                if ($existingLink === true) {
                    $this->dbLog()->info(
                        "User " . currentUser()->getName() . " tried to link an already linked twitter account.",
                        [
                            'userId' => currentUser()->getId(),
                            'userName' => currentUser()->getName(),
                            'twitterAccount' => $twitterUser
                        ]
                    );

                    $this->addMessage('accountAlreadyLinkedToDifferentUser', 'danger');
                    $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                }

                $authProviderUser = (new AuthProviderUser())
                    ->setIdentifier($twitterUser['user_id'])
                    ->setProvider('twitter')
                    ->setOauthToken($twitterUser['oauth_token'])
                    ->setOauthTokenSecret($twitterUser['oauth_token_user'])
                    ->setScreenName($twitterUser['screen_name'])
                    ->setUserId(currentUser()->getId());

                $link = $authProvider->linkProviderWithUser($authProviderUser);

                if ($link === true) {
                    $this->dbLog()->info(
                        "User " . currentUser()->getName() . " has linked a twitter account.",
                        [
                            'userId' => currentUser()->getId(),
                            'userName' => currentUser()->getName(),
                            'twitterAccount' => $twitterUser
                        ]
                    );

                    $this->addMessage('linkSuccess');
                    $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                }

                $this->dbLog()->error(
                    "User " . currentUser()->getName() . " could not link his twitter account.",
                    [
                        'userId' => currentUser()->getId(),
                        'userName' => currentUser()->getName(),
                        'twitterAccount' => $twitterUser
                    ]
                );

                $this->addMessage('linkFailed', 'danger');
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
            }

            if ($existingLink === true) {
                $userId = $authProvider->getUserIdByProvider('twitter', $twitterUser['user_id']);

                if (is_null($userId)) {
                    $this->addMessage('couldNotFindRequestedUser');
                    $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
                }

                $_SESSION['user_id'] = $userId;

                $this->addMessage('loginSuccess');
                $this->redirect('/');
            }

            if ($existingLink === false && ! loggedIn() && ! $this->getConfig()->get('regist_accept')) {
                $this->addMessage('twitterauth.messages.registrationNotAllowed', 'danger');
                $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
            }

            array_dot_set($_SESSION, 'twitterauth.login', $twitterUser);
            array_dot_set($_SESSION, 'twitterauth.login.expires', strtotime('+5 minutes'));

            $this->redirect(['action' => 'regist']);
        } catch (\Exception $e) {
            $this->addMessage('twitterauth.authenticationfailure', 'danger');

            if (loggedIn()) {
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
            } else {
                $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
            }
        }
    }

    /**
     * @return DbLog
     */
    protected function dbLog()
    {
        if ($this->dbLog instanceof DbLog) {
            return $this->dbLog;
        }

        return $this->dbLog = new DbLog();
    }
}
