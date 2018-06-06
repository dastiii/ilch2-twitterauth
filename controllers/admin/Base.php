<?php

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
