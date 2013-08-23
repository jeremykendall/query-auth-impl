<?php
/**
 * Query Auth Example Implementation
 *
 * @copyright 2013 Jeremy Kendall
 * @license https://github.com/jeremykendall/query-auth-impl/blob/master/LICENSE.md MIT
 * @link https://github.com/jeremykendall/query-auth-impl
 */

namespace Example\Dao;

use QueryAuth\Storage\SignatureStorage;

/**
 * Handles data access for signatures table
 */
class Signature implements SignatureStorage
{
    /**
     * @var \PDO Database connection
     */
    private $db;

    /**
     * Public constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key, $signature)
    {
        $sql = 'SELECT * FROM signatures WHERE apikey = :key AND signature = :signature';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':apikey', $key);
        $stmt->bindValue(':signature', $signature);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * {@inheritDoc}
     */
    public function save($key, $signature, $expires)
    {
        $sql = 'INSERT INTO signatures (apikey, signature, expires) VALUES (:apikey, :signature, :expires)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':apikey', $key);
        $stmt->bindValue(':signature', $signature);
        $stmt->bindValue(':expires', $expires);

        return $stmt->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function purge()
    {
        $sql = 'DELETE FROM signatures WHERE expires < :now';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':expires', (int) gmdate('U'));

        return $stmt->execute();
    }
}
