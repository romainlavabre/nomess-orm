<?php
/** @noinspection PhpUndefinedConstantInspection */


namespace Nomess\Component\Orm\Driver\Pdo;


use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\Driver\Instance;

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
    private \PDO  $connection;
    private array $config;
    
    
    public function __construct()
    {
        $this->config = require self::CACHE_PATH;
    }
    
    
    public function getConnection(): \PDO
    {
        if( !isset( Instance::$connection ) ) {
            $this->establishConnection();
        }
        
        return Instance::$connection;
    }
    
    
    public function getDatabase(): string
    {
        return $this->config[self::DB_NAME];
    }
    
    
    private function establishConnection(): void
    {
        $db = new \PDO( $this->getStatement( $this->config ), $this->config[self::USERNAME], $this->config[self::PASSWORD], array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->config[self::ENCODING]
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
