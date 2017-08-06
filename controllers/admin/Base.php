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

use Ilch\Controller\Admin;

class Base extends Admin {
    /**
     * Init function
     */
    public function init()
    {
        $items = [
            [
                'name' => 'twitterauth.menu.apikeys',
                'active' => $this->isActive('index', 'index'),
                'icon' => 'fa fa-cog',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index'])
            ],
            [
                'name' => 'twitterauth.menu.logs',
                'active' => $this->isActive('log', 'index'),
                'icon' => 'fa fa-list',
                'url' => $this->getLayout()->getUrl(['controller' => 'log', 'action' => 'index'])
            ]
        ];

        $this->getLayout()->addMenu(
            'twitterauth.menu.signinwithtwitter',
            $items
        );
    }

    /**
     * Checks if the menu item is active
     *
     * @param $controller
     * @param $action
     *
     * @return bool
     */
    protected function isActive($controller, $action)
    {
        return $this->getRequest()->getControllerName() === $controller && $this->getRequest()->getActionName() === $action;
    }
}
