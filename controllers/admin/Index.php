<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Twitterauth\Controllers\Admin;

class Index extends \Ilch\Controller\Admin
{
    public function init()
    {
        $items = [
            [
                'name' => 'api_keys',
                'active' => false,
                'icon' => 'fa fa-cog',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index'])
            ],
        ];

        $this->getLayout()->addMenu(
            'menuSignInWithTwitter',
            $items
        );
    }

    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
                ->add($this->getTranslator()->trans('menuSignInWithTwitter'), ['action' => 'index'])
                ->add($this->getTranslator()->trans('api_keys'), ['action' => 'index']);

        $output = [
            'consumerKey' => $this->getConfig()->get('twitterauth_consumer_key'),
            'consumerSecret' => $this->getConfig()->get('twitterauth_consumer_secret'),
            'accessToken' => $this->getConfig()->get('twitterauth_access_token'),
            'accessTokenSecret' => $this->getConfig()->get('twitterauth_access_token_secret'),
        ];

        $this->getView()->set('twitterauth', $output);
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->getConfig()->set('twitterauth_consumer_key', $this->getRequest()->getPost('consumerKey'));
            $this->getConfig()->set('twitterauth_consumer_secret', $this->getRequest()->getPost('consumerSecret'));
            $this->getConfig()->set('twitterauth_access_token', $this->getRequest()->getPost('accessToken'));
            $this->getConfig()->set('twitterauth_access_token_secret', $this->getRequest()->getPost('accessTokenSecret'));
            $this->addMessage('saveSuccess');
        }
        
        $this->redirect(['action' => 'index']);
    }
}
