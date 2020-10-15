<?php


namespace Nomess\Component\Orm\Cli;


use Nomess\Component\Cli\Executable\ExecutableInterface;
use Nomess\Component\Cli\Interactive\InteractiveInterface;
use Nomess\Component\Config\ConfigStoreInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class DatabaseCreate implements ExecutableInterface
{
    
    private const CONFIG_NAME = 'orm';
    private ConfigStoreInterface     $configStore;
    private InteractiveInterface     $interactiveHandler;
    
    
    public function __construct(
        ConfigStoreInterface $configStore,
        InteractiveInterface $interactiveHandler )
    {
        $this->configStore        = $configStore;
        $this->interactiveHandler = $interactiveHandler;
    }
    
    
    public function exec( array $command ): void
    {
        $config   = $this->configStore->get( self::CONFIG_NAME )['connection'];
        $database = $config['dbname'];
        $server   = $config['server'];
        $port     = $config['port'];
        $username = $config['user'];
        $password = $config['password'];
        $host     = $config['host'];
        $encoding = $config['encode'];
        
        $db = new \PDO( $server . ':host=' . $host . ';port=' . $port, $username, $password, array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $encoding
        ) );
        
        try {
            $query = NULL;
            
            if($server === 'mysql'){
                $query = 'CREATE DATABASE `' . $database . '`;';
            }elseif($server === 'pgsql'){
                $query = 'CREATE DATABASE "' . $database . '";';
            }
            
            $statment = $db->query( $query);
            if( $statment instanceof \PDOStatement ) {
                $statment->execute();
                $this->interactiveHandler->writeColorGreen( 'Database created' );
            } else {
                $this->interactiveHandler->writeColorRed( 'An error occured' );
            }
        } catch( \Throwable $throwable ) {
            var_dump( $throwable->getMessage());
            echo $throwable->getMessage() . "\n";
        }
    }
}
