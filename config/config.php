<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\TwitterAuth\Config;

class Config extends \Ilch\Config\Install
{
    public $config = [
        'key' => 'twitterauth',
        'icon_small' => 'fa-twitter',
        'author' => 'Tobias Schwarz',
        'version' => '0.0.2',
        'languages' => [
            'de_DE' => [
                'name' => 'Anmelden mit Twitter',
                'description' => 'Ermöglicht die Anmeldung per Twitter.',
            ],
            'en_EN' => [
                'name' => 'Sign in with Twitter',
                'description' => 'Allows users to sign in through twitter.',
            ],
        ]
    ];

    public function install()
    {
        $this->db()->queryMulti($this->getInstallSql());
    }

    public function getInstallSql()
    {
        return "
            INSERT INTO `[prefix]_auth_providers`
                (`key`, `name`, `icon`)
            VALUES 
                ('twitter', 'Twitter', 'fa-twitter');

            INSERT INTO `[prefix]_auth_providers_modules`
                (`module`, `provider`, `auth_controller`, `auth_action`,
                `unlink_controller`, `unlink_action`)
            VALUES
                ('twitterauth', 'twitter', 'auth', 'index', 'auth', 'unlink');
        ";
    }

    public function getUpdate()
    {
        //
    }
}
