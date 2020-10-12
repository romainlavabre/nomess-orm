<?php


namespace Nomess\Component\Orm;


use Nomess\Component\Cli\Executable\ExecutableInterface;
use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Config\Exception\ConfigurationNotFoundException;
use Nomess\Component\Orm\Cache\Builder\CacheBuilderInterface;
use Nomess\Component\Orm\Cache\Builder\Mysql\CacheBuilder;
use Nomess\Component\Orm\Cache\CacheHandler;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Cli\DatabaseCreate;
use Nomess\Component\Orm\Cli\DatabaseDrop;
use Nomess\Component\Orm\Cli\DatabaseUpdate;
use Nomess\Component\Orm\Cli\Installer;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\Driver\Pdo\PdoDriver;
use Nomess\Component\Orm\Handler\DeleteHandler;
use Nomess\Component\Orm\Handler\DeleteHandlerInterface;
use Nomess\Component\Orm\Handler\FindHandler;
use Nomess\Component\Orm\Handler\FindHandlerInterface;
use Nomess\Component\Orm\Handler\PersistHandler;
use Nomess\Component\Orm\Handler\PersistHandlerInterface;
use Nomess\Component\Orm\Handler\RawHandler;
use Nomess\Component\Orm\Handler\RawHandlerInterface;
use Nomess\Component\Orm\Handler\SaveHandler;
use Nomess\Component\Orm\Handler\SaveHandlerInterface;
use Nomess\Component\Orm\Handler\StoreHandler;
use Nomess\Component\Orm\Handler\StoreHandlerInterface;
use Nomess\Component\Orm\QueryWriter\Mysql\CreateQuery;
use Nomess\Component\Orm\QueryWriter\Mysql\DeleteQuery;
use Nomess\Component\Orm\QueryWriter\Mysql\FreeQuery;
use Nomess\Component\Orm\QueryWriter\Mysql\SelectRelationQuery;
use Nomess\Component\Orm\QueryWriter\Mysql\UpdateQuery;
use Nomess\Component\Orm\QueryWriter\QueryCreateInterface;
use Nomess\Component\Orm\QueryWriter\QueryDeleteInterface;
use Nomess\Component\Orm\QueryWriter\QueryFreeInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinRelationInterface;
use Nomess\Component\Orm\QueryWriter\QuerySelectInterface;
use Nomess\Component\Orm\QueryWriter\QueryUpdateInterface;
use Nomess\Component\Orm\QueryWriter\QueryUpdateNtoNInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class NomessInstaller implements \Nomess\Installer\NomessInstallerInterface
{
    
    private const CONFIG_NAME = 'orm';
    private ConfigStoreInterface $configStore;
    
    
    public function __construct( ConfigStoreInterface $configStore )
    {
        $this->configStore = $configStore;
    }
    
    
    /**
     * @inheritDoc
     */
    public function container(): array
    {
        try {
            $config = $this->configStore->get( self::CONFIG_NAME );
        } catch( ConfigurationNotFoundException $e ) {
            return [];
        }
        
        $container = [
            CacheHandlerInterface::class       => CacheHandler::class,
            DeleteHandlerInterface::class      => DeleteHandler::class,
            FindHandlerInterface::class        => FindHandler::class,
            PersistHandlerInterface::class     => PersistHandler::class,
            SaveHandlerInterface::class        => SaveHandler::class,
            RawHandlerInterface::class         => RawHandler::class,
            StoreHandlerInterface::class       => StoreHandler::class,
            TransactionSubjectInterface::class => EntityManager::class,
            DriverHandlerInterface::class      => PdoDriver::class,
            EntityManagerInterface::class      => EntityManager::class,
        ];
        
        
        if( $config['connection']['server'] === 'mysql' ) {
            $container[QueryCreateInterface::class]       = CreateQuery::class;
            $container[QueryUpdateInterface::class]       = UpdateQuery::class;
            $container[QueryDeleteInterface::class]       = DeleteQuery::class;
            $container[QuerySelectInterface::class]       = QuerySelectInterface::class;
            $container[QueryUpdateNtoNInterface::class]   = QueryUpdateNtoNInterface::class;
            $container[QueryFreeInterface::class]         = FreeQuery::class;
            $container[QueryJoinRelationInterface::class] = SelectRelationQuery::class;
            $container[CacheBuilderInterface::class]      = CacheBuilder::class;
        } elseif( $config['connection']['server'] === 'pgsql' ) {
            $container[QueryCreateInterface::class]       = \Nomess\Component\Orm\QueryWriter\PostgreSql\CreateQuery::class;
            $container[QueryUpdateInterface::class]       = \Nomess\Component\Orm\QueryWriter\PostgreSql\UpdateQuery::class;
            $container[QueryDeleteInterface::class]       = \Nomess\Component\Orm\QueryWriter\PostgreSql\DeleteQuery::class;
            $container[QuerySelectInterface::class]       = \Nomess\Component\Orm\QueryWriter\PostgreSql\SelectQuery::class;
            $container[QueryUpdateNtoNInterface::class]   = \Nomess\Component\Orm\QueryWriter\PostgreSql\UpdateNtoNQuery::class;
            $container[QueryFreeInterface::class]         = \Nomess\Component\Orm\QueryWriter\PostgreSql\FreeQuery::class;
            $container[QueryJoinRelationInterface::class] = \Nomess\Component\Orm\QueryWriter\PostgreSql\SelectRelationQuery::class;
            $container[CacheBuilderInterface::class]      = \Nomess\Component\Orm\Cache\Builder\PostgreSql\CacheBuilder::class;
        }
        
        return $container;
    }
    
    
    /**
     * @inheritDoc
     */
    public function controller(): array
    {
        return [];
    }
    
    
    /**
     * @inheritDoc
     */
    public function cli(): array
    {
        return [
            'nomess/orm'       => NULL,
            'database:create'  => [
                ExecutableInterface::COMMENT   => 'Create your database',
                ExecutableInterface::CLASSNAME => DatabaseCreate::class
            ],
            'database:install' => [
                ExecutableInterface::COMMENT   => 'Install your database',
                ExecutableInterface::CLASSNAME => DatabaseUpdate::class
            ],
            'database:update'  => [
                ExecutableInterface::COMMENT   => 'Update your database',
                ExecutableInterface::CLASSNAME => DatabaseUpdate::class
            ],
            'database:drop'    => [
                ExecutableInterface::COMMENT   => 'Drop your database',
                ExecutableInterface::CLASSNAME => DatabaseDrop::class
            ]
        ];
    }
    
    
    /**
     * @inheritDoc
     */
    public function exec(): ?string
    {
        return Installer::class;
    }
}
