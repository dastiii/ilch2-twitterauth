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
        'version' => '1.0.0-beta.1',
        'languages' => [
            'de_DE' => [
                'name' => 'Anmelden mit Twitter',
                'description' => 'Ermöglicht Benutzern die Anmeldung per Twitter.',
            ],
            'en_EN' => [
                'name' => 'Sign in with Twitter',
                'description' => 'Allows users to sign in through twitter.',
            ],
        ],
        'phpVersion' => '5.6',
        'ilchCore' => '2.0.0'
    ];

    public function install()
    {
        if (! $this->providerExists()) {
            $this->db()
                ->insert('auth_providers')
                ->values([
                    'key' => 'twitter',
                    'name' => 'Twitter',
                    'icon' => 'fa-twitter'
                ])
                ->execute();
        }

        $this->db()->query('
            CREATE TABLE `[prefix]_twitterauth_log` (
              `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
              `type` varchar(50) DEFAULT \'info\',
              `message` text,
              `data` text,
              `created_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->db()
            ->insert('auth_providers_modules')
            ->values([
                'module' => 'twitterauth',
                'provider' => 'twitter',
                'auth_controller' => 'auth',
                'auth_action' => 'index',
                'unlink_controller' => 'auth',
                'unlink_action' => 'unlink',
            ])
            ->execute();
    }

    public function uninstall()
    {
        $this->db()
            ->delete()
            ->from('auth_providers_modules')
            ->where(['module' => 'twitterauth'])
            ->execute();

        $this->db()->query('DROP TABLE IF EXISTS [prefix]_twitterauth_log');
    }

    public function getUpdate()
    {
        //
    }

    /**
     * @return boolean
     */
    private function providerExists()
    {
        return (bool) $this->db()
            ->select('key')
            ->from('auth_providers')
            ->where(['key' => 'twitter'])
            ->useFoundRows()
            ->execute()
            ->getFoundRows();
    }
}
