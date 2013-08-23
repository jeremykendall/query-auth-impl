<?php
/**
 * Query Auth Example Implementation
 *
 * @copyright 2013 Jeremy Kendall
 * @license https://github.com/jeremykendall/query-auth-impl/blob/master/LICENSE.md MIT
 * @link https://github.com/jeremykendall/query-auth-impl
 */

namespace Example\Composer\Script;

use Composer\Script\Event;

/**
 * Checks to see if the application's database has been created. If not, the
 * database is created.
 */
class DbExists
{
    /**
     * Checks for database and configures database if it does not exist
     *
     * @param  Event        $event
     * @throws PDOException
     */
    public static function createIfNotExists(Event $event)
    {
        $root = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
        $config = include $root . '/config.php';

        $io = $event->getIO();

        $io->write('Checking for database . . .', true);

        $dbExists = file_exists($config['database']);

        if (!$dbExists) {
            try {
                $io->write('Creating new database . . .', true);
                $db = new \PDO(
                    $config['pdo']['dsn'],
                    $config['pdo']['username'],
                    $config['pdo']['password'],
                    $config['pdo']['options']
                );
                $db->exec(file_get_contents($root . '/scripts/sql/schema.sql'));
                $db = null;
            } catch (\PDOException $e) {
                throw $e;
            }
            $io->write("Done!", true);
        } else {
            $io->write('Database found.', true);
        }
    }
}
