<?php

namespace Modules\Twitterauth\Controllers\Admin;

use Modules\Twitterauth\Mappers\DbLog;

class Log extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('twitterauth.menu.signinwithtwitter'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('twitterauth.menu.logs'), ['action' => 'index']);

        $dbLog = new DbLog();

        $this->getView()->set('logs', $dbLog->getAll());
    }

    public function clearAction()
    {
        if (! $this->getRequest()->isPost()) {
            $this->addMessage('twitterauth.methodnotallowed', 'danger');

            $this->redirect(['action' => 'index']);
        }

        $dbLog = new DbLog();

        try {
            $dbLog->clear();

            $this->addMessage('twitterauth.loghasbeencleared');

            $this->redirect(['action' => 'index']);
        } catch (\Exception $e) {
            $this->addMessage('twitterauth.couldnotclearlog', 'danger');

            $this->redirect(['action' => 'index']);
        }
    }

    public function deleteAction()
    {
        $dbLog = new DbLog();

        try {
            $dbLog->delete($this->getRequest()->getParam('id'));

            $this->addMessage('twitterauth.logdeletedsuccessful');

            $this->redirect(['action' => 'index']);
        } catch (\Exception $e) {
            $this->addMessage('twitterauth.logdeletederror', 'danger');

            $this->redirect(['action' => 'index']);
        }
    }
}
