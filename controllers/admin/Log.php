<?php
// COPYRIGHT (c) 2016 Tobias Schwarz
//
// MIT License
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
// LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
// OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/**
 * @copyright Tobias Schwarz
 * @author Tobias Schwarz <code@tobias-schwarz.me>
 * @license MIT
 */

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
