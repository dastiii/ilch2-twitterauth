<?php
/**
 * @copyright Ilch 2.0
 */

namespace Modules\Twitterauth\Controllers;

use Modules\Twitterauth\Libs\TwitterAuth;

use Modules\User\Mappers\AuthProvider;
use Modules\User\Models\AuthProviderUser;

use Modules\User\Models\User;
use Modules\User\Mappers\User as UserMapper;
use Modules\User\Mappers\Group;

use Modules\User\Service\Password as PasswordService;

use Ilch\Validation;

class Auth extends \Ilch\Controller\Frontend
{
    public function indexAction()
    {
        if (loggedIn()) {
            $authProvider = new AuthProvider;

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
            ->setCallback('http://localhost:8000/index.php/twitterauth/auth/callback')
            ->exec();

        if (!$auth->hasError()) {
            $this->redirect(
                'https://api.twitter.com/oauth/authenticate?oauth_token='.$auth->getResult()['oauth_token']
            );
        } else {
            echo $auth->getErrors()[0]->code;
            echo $auth->getErrors()[0]->message;
        }
    }

    public function callbackAction()
    {
        $oauth_verifier = $this->getRequest()->getQuery('oauth_verifier');
        $oauth_token = $this->getRequest()->getQuery('oauth_token');

        if (is_null($oauth_verifier) || is_null($oauth_token)) {
            $this->addMessage('badRequest', 'danger');
            $this->redirect('/');
        }

        $auth = (new TwitterAuth())
            ->setMethod('POST')
            ->setUrl('https://api.twitter.com/oauth/access_token')
            ->setConsumerKey($this->getConfig()->get('twitterauth_consumer_key'))
            ->setConsumerSecret($this->getConfig()->get('twitterauth_consumer_secret'))
            ->setToken($oauth_token)
            ->setTokenSecret('')
            ->addField('oauth_verifier', $oauth_verifier)
            ->setWithout('oauth_callback')
            ->exec();

        if (!$auth->hasError()) {
            $oauth_token = isset($auth->getResult()['oauth_token']) ? $auth->getResult()['oauth_token'] : null;
            $oauth_token_secret = isset($auth->getResult()['oauth_token_secret']) ? $auth->getResult()['oauth_token_secret'] : null;

            $verify = (new TwitterAuth())
                ->setMethod('GET')
                ->setUrl('https://api.twitter.com/1.1/account/verify_credentials.json')
                ->setConsumerKey($this->getConfig()->get('twitterauth_consumer_key'))
                ->setConsumerSecret($this->getConfig()->get('twitterauth_consumer_secret'))
                ->setToken($oauth_token)
                ->setTokenSecret($oauth_token_secret)
                ->setWithout('oauth_callback')
                ->exec();

            if (!$verify->hasError()) {
                $authProvider = new AuthProvider();
                $existingLink = $authProvider->providerAccountIsLinked('twitter', $verify->getResult()->id_str);

                if (loggedIn()) {
                    if ($authProvider->hasProviderLinked('twitter', currentUser()->getId())) {
                        $this->addMessage('providerAlreadyLinked', 'danger');
                        $this->redirect('/');
                    }

                    if ($existingLink === true) {
                        $this->addMessage('accountAlreadyLinkedToDifferentUser', 'danger');
                        $this->redirect('/');
                    } else {
                        $authProviderUser = (new AuthProviderUser())
                            ->setIdentifier($verify->getResult()->id_str)
                            ->setProvider('twitter')
                            ->setOauthToken($oauth_token)
                            ->setOauthTokenSecret($oauth_token_secret)
                            ->setScreenName($verify->getResult()->screen_name)
                            ->setUserId(currentUser()->getId());

                        $link = $authProvider->linkProviderWithUser($authProviderUser);
                        
                        if ($link === true) {
                            $this->addMessage('linkSuccess');
                            $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'providers']);
                        } else {
                            $this->addMessage('linkFailed', 'danger');
                            $this->redirect('/');
                        }
                    }
                } else {
                    if ($existingLink === true) {
                        // TODO: Admin panel setting?
                        $remember = false;
                        $user_id = $authProvider->getUserIdByProvider('twitter', $verify->getResult()->id_str);

                        if (is_null($user_id)) {
                            $this->addMessage('couldNotFindRequestedUser');
                            $this->redirect('/');
                        }

                        if ($remember === false) {
                            $_SESSION['user_id'] = $user_id;
                        } else {
                            // Remembertoken bla
                        }

                        $this->addMessage('loginSuccess');
                        $this->redirect('/');
                    } else {
                        $_SESSION['oauth_login'] = [
                            'oauth_token' => $auth->getResult()['oauth_token'],
                            'oauth_token_secret' => $auth->getResult()['oauth_token_secret'],
                            'data' => $verify->getResult(),
                            'timestamp' => strtotime("+10 minutes"),
                        ];
                        
                        $this->redirect(['action' => 'regist']);
                    }
                }
            } else {
                $this->addMessage('couldNotRetrieveCredentials', 'danger');
                $this->redirect(['module' => 'user', 'controller' => 'regist', 'action' => 'index']);
            }
        } else {
            $this->addMessage('requestDenied', 'danger');
            $this->redirect(['module' => 'user', 'controller' => 'regist', 'action' => 'index']);
        }
    }

    public function registAction()
    {
        if (isset($_SESSION['oauth_login']) && $_SESSION['oauth_login']['timestamp'] >= time()) {
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
        } else {
            $this->addMessage('registExpired', 'danger');
            $this->redirect(['module' => 'user', 'controller' => 'regist', 'action' => 'index']);
        }
    }

    public function saveAction()
    {
        if (! $this->getRequest()->isPost()) {
            $this->addMessage('badRequest');
            $this->redirect('/');
        }
        
        if (! isset($_SESSION['oauth_login']) && $_SESSION['oauth_login']['timestamp'] < time()) {
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

            $user = new User();
            $user->setName($input['userName']);
            $user->setPassword((new PasswordService())->hash(PasswordService::generateSecurePassword(32)));
            $user->setEmail($input['email']);
            $user->setDateCreated($currentDate->format("Y-m-d H:i:s", true));
            $user->addGroup($userGroup);

            // if ($this->getConfig()->get('regist_confirm') == 0) {
                $user->setDateConfirmed($currentDate->format("Y-m-d H:i:s", true));
            // } else {
            //     $confirmedCode = md5(uniqid(rand()));
            //     $user->setConfirmed(0);
            //     $user->setConfirmedCode($confirmedCode);
            // }
            $userId = $registMapper->save($user);

            $oauth = $_SESSION['oauth_login'];

            $authProviderUser = (new AuthProviderUser())
                ->setIdentifier($oauth['data']->id_str)
                ->setProvider('twitter')
                ->setOauthToken($oauth['oauth_token'])
                ->setOauthTokenSecret($oauth['oauth_token_secret'])
                ->setScreenName($oauth['data']->screen_name)
                ->setUserId($userId);

            unset($_SESSION['oauth_login']);

            $link = (new AuthProvider())->linkProviderWithUser($authProviderUser);
            
            if ($link === true) {
                $_SESSION['user_id'] = $userId;
                
                $this->addMessage('linkSuccess');
                $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'index']);
            } else {
                $this->addMessage('linkFailed', 'danger');
                $this->redirect('/');
            }

            $this->addMessage('welcome');
            $this->redirect(['module' => 'user', 'controller' => 'panel', 'action' => 'index']);
        } else {
            $_SESSION['errors'] = $validation->getErrorBag()->getErrors();
            $_SESSION['old'] = $input;

            $this->redirect(['action' => 'regist']);
        }
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
                } else {
                    $this->addMessage('couldNotUnlink', 'danger');
                    $this->redirect('/');
                }
            } else {
                $this->addMessage('badRequest', 'danger');
                $this->redirect('/');
            }
        } else {
            $this->addMessage('notAuthenticated', 'danger');
            $this->redirect('/');
        }
    }
}
