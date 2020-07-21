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
        if (loggedIn()) {
            // Authenticated users should not be able to register another account while logged in.
            $this->addMessage('twitterauth.messages.youAlreadyHaveAnAccount', 'danger');
            $this->redirect('/');
        }

        if ($this->isAuthProcessExpired()) {
            $this->addMessage('twitterauth.messages.registExpired', 'danger');
            $this->redirect([
                'module' => 'user',
                'controller' => 'regist',
                'action' => 'index'
            ]);
        }

        $this->getView()->set('rules', $this->getConfig()->get('regist_rules'));
        $this->getView()->set('user', array_dot($_SESSION, 'twitterauth.login'));
    }

    /**
     * Saves the new user to the database.
     */
    public function saveAction()
    {
        if (! $this->getRequest()->isPost()) {
            $this->addMessage('twitterauth.messages.methodNotAllowed');
            $this->redirect('/');
        }

        if ($this->isAuthProcessExpired()) {
            $this->addMessage('twitterauth.messages.registExpired');
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

        if (! $validation->isValid()) {
            $this->addMessage(implode(', ', $validation->getErrorBag()->getErrorMessages()), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(['action' => 'regist']);
        }

        try {
            $user = $this->createUser($input['userName'], $input['email']);
            $twitterUser = array_dot($_SESSION, 'twitterauth.login');

            $authProviderUser = (new AuthProviderUser())
                ->setIdentifier($twitterUser['user_id'])
                ->setProvider('twitter')
                ->setOauthToken($twitterUser['oauth_token'])
                ->setOauthTokenSecret($twitterUser['oauth_token_secret'])
                ->setScreenName($twitterUser['screen_name'])
                ->setUserId($user->getId());

            if ((new AuthProvider())->linkProviderWithUser($authProviderUser)) {
                $_SESSION['user_id'] = $user->getId();

                $this->addMessage('twitterauth.linksuccess');
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'index']);
            }

            $this->addMessage('twitterauth.linkfailed', 'danger');
            $this->redirect('/');
        } catch (\Exception $e) {
            $this->addMessage('twitterauth.messages.couldNotCreateUser', 'danger');
            $this->redirect('/');
        }
    }

    public function unlinkAction()
    {
        if (loggedIn() === false) {
            $this->addMessage('twitterauth.notauthenticated', 'danger');
            $this->redirect('/');
        }

        if ($this->getRequest()->isPost() === false) {
            $this->addMessage('twitterauth.badrequest', 'danger');
            $this->redirect('/');
        }

        $authProvider = new AuthProvider();
        if ($authProvider->unlinkUser('twitter', currentUser()->getId())) {
            $this->addMessage('twitterauth.unlinkedsuccessfully');
            $this->redirect([
                'module' => 'user',
                'controller' => 'panel',
                'action' => 'providers'
            ]);
        }

        $this->addMessage('twitterauth.couldnotunlink', 'danger');
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
            $this->addMessage('twitterauth.authenticationFailure', 'danger');

            $this->backToStart();
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
            $providerAccountIsAlreadyLinked = $authProvider->providerAccountIsLinked('twitter', $twitterUser['user_id']);

            // Redirect the user to the regist action since he is actually registering a new account.
            if (loggedIn() === false) {
                // Provider account is linked, login as the linked user.
                if ($providerAccountIsAlreadyLinked) {
                    $userId = $authProvider->getUserIdByProvider('twitter', $twitterUser['user_id']);

                    if (is_null($userId)) {
                        $this->addMessage('twitterauth.couldNotFindRequestedUser');
                        $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
                    }

                    $_SESSION['user_id'] = $userId;

                    $this->addMessage('twitterauth.loginSuccessful');
                    $this->redirect('/');
                }

                // If no new registrations are allowed, redirect back to login.
                if (! $this->getConfig()->get('regist_accept')) {
                    $this->addMessage('twitterauth.messages.registrationNotAllowed', 'danger');
                    $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
                }

                array_dot_set($_SESSION, 'twitterauth.login', $twitterUser);
                array_dot_set($_SESSION, 'twitterauth.login.expires', strtotime('+5 minutes'));

                $this->redirect(['action' => 'regist']);
            }

            // Redirect back if the user had this provider already linked with his account.
            if ($authProvider->hasProviderLinked('twitter', currentUser()->getId())) {
                $this->dbLog()->info(
                    "User " . currentUser()->getName() . " had provider already linked.",
                    [
                        'userId' => currentUser()->getId(),
                        'userName' => currentUser()->getName(),
                        'twitterAccount' => $twitterUser
                    ]
                );

                $this->addMessage('twitterauth.providerAlreadyLinked', 'danger');
                $this->redirect([
                    'module' => 'user',
                    'controller' => 'panel',
                    'action' => 'providers'
                ]);
            }

            // Redirect back if the user tried to link his account to an already used provider account.
            if ($providerAccountIsAlreadyLinked) {
                $this->dbLog()->info(
                    "User " . currentUser()->getName() . " tried to link an already linked twitter account.",
                    [
                        'userId' => currentUser()->getId(),
                        'userName' => currentUser()->getName(),
                        'twitterAccount' => $twitterUser
                    ]
                );

                $this->addMessage('twitterauth.accountAlreadyLinkedToDifferentUser', 'danger');
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
            }

            $authProviderUser = (new AuthProviderUser())
                ->setIdentifier($twitterUser['user_id'])
                ->setProvider('twitter')
                ->setOauthToken($twitterUser['oauth_token'])
                ->setOauthTokenSecret($twitterUser['oauth_token_user'])
                ->setScreenName($twitterUser['screen_name'])
                ->setUserId(currentUser()->getId());

            if ($authProvider->linkProviderWithUser($authProviderUser)) {
                $this->dbLog()->info(
                    "User " . currentUser()->getName() . " has linked a twitter account.",
                    [
                        'userId' => currentUser()->getId(),
                        'userName' => currentUser()->getName(),
                        'twitterAccount' => $twitterUser
                    ]
                );

                $this->addMessage('twitterauth.linksuccess');
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

            $this->addMessage('twitterauth.linkFailed', 'danger');
            $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
        } catch (\Exception $e) {
            $this->addMessage('twitterauth.authenticationFailure', 'danger');

            $this->backToStart();
        }
    }

    /**
     * @return Boolean
     */
    protected function isAuthProcessExpired()
    {
        return ! array_dot($_SESSION, 'twitterauth.login') || array_dot($_SESSION, 'twitterauth.login.expires') < time();
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

    /**
     * Registers a user with the given name and email
     *
     * @param string $username
     * @param string $email
     * @return User
     * @throws \Exception
     */
    protected function createUser(string $username, string $email): User
    {
        $registMapper = new UserMapper();
        $groupMapper = new Group();
        $userGroup = $groupMapper->getGroupById(2);
        $currentDate = new \Ilch\Date();
        $randomPassword = (new PasswordService())
            ->hash(PasswordService::generateSecurePassword(32));

        $user = (new User())
            ->setName($username)
            ->setPassword($randomPassword)
            ->setEmail($email)
            ->setDateCreated($currentDate)
            ->addGroup($userGroup)
            ->setDateConfirmed($currentDate);

        if ($userId = $registMapper->save($user)) {
            $user->setId($userId);
        }

        return $user;
    }

    // Redirects the user back to start. A loggedIn
    // user is redirected to his profile page whereas
    // a guest is redirected back to the login page.
    protected function backToStart()
    {
        if (loggedIn()) {
            $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
        }

        $this->redirect(['module' => 'user', 'controller' => 'login', 'action' => 'index']);
    }
}
