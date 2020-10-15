<?php


namespace Nomess\Component\Orm\QueryWriter\PostgreSql;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryDeleteInterface;
use Nomess\Component\Orm\Store;

class DeleteQuery implements QueryDeleteInterface
{
    
    private const QUERY_DELETE = 'DELETE FROM ';
    private const QUERY_WHERE  = ' WHERE ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler
    )
    {
        $this->cacheHandler  = $cacheHandler;
        $this->driverHandler = $driverHandler;
    }
    
    
    public function getQuery( object $object ): \PDOStatement
    {
        
        $cache = $this->cacheHandler->getCache( get_class( $object ) );
        
        $statement = $this->driverHandler->getConnection()->prepare(
            self::QUERY_DELETE . $this->queryTable( $cache ) . self::QUERY_WHERE . $this->queryWhere( $object ) . ';'
        );
        
        return $statement;
    }
    
    
    private function queryTable( array $cache ): string
    {
        return '"' . $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '"';
    }
    
    
    private function queryWhere( object $object ): string
    {
        $id = Store::getReflection( get_class( $object ), 'id' )->getValue( $object );
        
        return 'id = ' . $id;
    }
}
