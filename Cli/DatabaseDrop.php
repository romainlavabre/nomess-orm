<?php


namespace Nomess\Component\Orm\Cli;


use Nomess\Component\Cli\Interactive\InteractiveInterface;
use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class DatabaseDrop implements \Nomess\Component\Cli\Executable\ExecutableInterface
{
    
    private const CONFIG_NAME = 'orm';
    private DriverHandlerInterface $driverHandler;
    private ConfigStoreInterface   $configStore;
    private InteractiveInterface   $interactiveHandler;
    
    
    public function __construct(
        DriverHandlerInterface $driverHandler,
        ConfigStoreInterface $configStore,
        InteractiveInterface $interactiveHandler )
    {
        $this->driverHandler      = $driverHandler;
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
            $statment = $db->query( 'DROP DATABASE `' . $database . '`' );
            
            if( $statment instanceof \PDOStatement ) {
                $statment->execute();
                $this->interactiveHandler->writeColorGreen( 'Database droped' );
            } else {
                $this->interactiveHandler->writeColorRed( 'An error occured' );
            }
        } catch( \Throwable $throwable ) {
            echo $throwable->getMessage() . "\n";
        }
    }
}
