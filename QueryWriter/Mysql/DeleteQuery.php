<?php


namespace Newwebsouth\Orm\QueryWriter\Mysql;


use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Newwebsouth\Orm\QueryWriter\QueryDeleteInterface;
use Newwebsouth\Orm\Store;

class DeleteQuery implements QueryDeleteInterface
{
    
    private const QUERY_DELETE = 'DELETE FROM ';
    private const QUERY_WHERE  = ' WHERE ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    
    
    public function getQuery( string $classname, object $object ): \PDOStatement
    {
        
        $cache = $this->cacheHandler->getCache( $classname );
        
        $statement = $this->driverHandler->getConnection()->prepare(
            self::QUERY_DELETE . $this->queryTable( $cache ) . self::QUERY_WHERE . $this->queryWhere( $object ) . ';'
        );
        
        return $statement;
    }
    
    
    private function queryTable( array $cache ): string
    {
        return $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
    }
    
    
    private function queryWhere( object $object ): string
    {
        $id = Store::getReflection( get_class( $object ), 'id' )->getValue( $object );
        
        return 'id = ' . $id;
    }
}
