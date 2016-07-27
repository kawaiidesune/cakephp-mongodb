<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Véronique Bellamy (http://veroniquebellamy.fr)
 * @link          http://veroniquebellamy.fr
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Driver;

// use Cake\Database\Dialect\MysqlDialectTrait;
use Cake\Database\Driver;
use Cake\Database\Query;
// use Cake\Database\Statement\MysqlStatement;
use PDO;

class Mongo extends Driver {
    use MongoDialectTrait;
    use PDODriverTrait; // TODO: Double check the syntax for this, ensuring that I can use the Mongo PDO with this...

    /**
     * Base configuration settings for MongoDB driver
     *
     * @var array
     */
    protected $_baseConfig = [
        'persistent' => true,
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'cake',
        'port' => '3306',
        'flags' => [],
        'encoding' => 'utf8',
        'timezone' => null,
        'init' => [],
    ];

    /**
     * Establishes a connection to the database server
     *
     * @return bool true on success
     */
    public function connect()
    {
        if ($this->_connection) {
            return true;
        }
        $config = $this->_config;

        if ($config['timezone'] === 'UTC') {
            $config['timezone'] = '+0:00';
        }

        if (!empty($config['timezone'])) {
            // $config['init'][] = sprintf("SET time_zone = '%s'", $config['timezone']);
            // TODO: Determine if (a) this is necessary with MongoDB and (b) how to do it, given the differences between NoSQL and MySQL.
        }
        if (!empty($config['encoding'])) {
            // $config['init'][] = sprintf("SET NAMES %s", $config['encoding']);
            // TODO: Determine if (a) this is necessary with MongoDB, considering it is supposed to return JSON and I would be surprised if it returned something other than UTF-8 or UTF-16 and (b) how to do it, given the differences between NoSQL and MySQL.
        }

        $config['flags'] += [
            PDO::ATTR_PERSISTENT => $config['persistent'],
            // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        if (!empty($config['ssl_key']) && !empty($config['ssl_cert'])) {
            $config['flags'][PDO::MYSQL_ATTR_SSL_KEY] = $config['ssl_key'];
            $config['flags'][PDO::MYSQL_ATTR_SSL_CERT] = $config['ssl_cert'];
        }
        if (!empty($config['ssl_ca'])) {
            $config['flags'][PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
        }

        if (empty($config['unix_socket'])) {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['encoding']}";
            // TODO: Double check if DSN is similiar to URI or has something to do, instead, with the Unix CLI.
        } else {
            $dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
            // TODO: Double check if DSN is similiar to URI or has something to do, instead, with the Unix CLI.
        }

        $this->_connect($dsn, $config);

        if (!empty($config['init'])) {
            $connection = $this->connection();
            foreach ((array)$config['init'] as $command) {
                $connection->exec($command);
            }
        }
        return true;
    }

    /**
     * Returns whether php is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled()
    {
        return in_array('mysql', PDO::getAvailableDrivers());
    }

    /**
     * Prepares a sql statement to be executed
     *
     * @param string|\Cake\Database\Query $query The query to prepare.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query)
    {
        $this->connect();
        $isObject = $query instanceof Query;
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query);
        $result = new MysqlStatement($statement, $this);
        if ($isObject && $query->bufferResults() === false) {
            $result->bufferResults(false);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDynamicConstraints()
    {
        return true;
    }
}
?>