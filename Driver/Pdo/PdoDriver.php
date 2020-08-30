<?php
/** @noinspection PhpUndefinedConstantInspection */


namespace Newwebsouth\Orm\Driver\Pdo;


use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Newwebsouth\Orm\Driver\Instance;

class PdoDriver implements DriverHandlerInterface
{
    
    private const CACHE_PATH = ROOT . 'config/database.php';
    private const SERVER     = 'server';
    private const HOST       = 'host';
    private const PORT       = 'port';
    private const DB_NAME    = 'dbname';
    private const ENCODING   = 'encode';
    private const USERNAME   = 'user';
    private const PASSWORD   = 'password';
    private \PDO $connection;
    
    
    public function getConnection(): \PDO
    {
        if( !isset( Instance::$connection ) ) {
            $this->establishConnection();
        }
        
        return Instance::$connection;
    }
    
    
    private function establishConnection(): void
    {
        $config = require self::CACHE_PATH;
        
        $db = new \PDO( $this->getStatement( $config ), $config[self::USERNAME], $config[self::PASSWORD], array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $config[self::ENCODING]
        ) );
        
        $db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        
        Instance::$connection = $db;
    }
    
    
    private function getStatement( array $config ): string
    {
        return $config[self::SERVER] .
               ':host=' . $config[self::HOST] .
               ';port=' . $config[self::PORT] .
               ';dbname=' . $config[self::DB_NAME] . '';
    }
}
