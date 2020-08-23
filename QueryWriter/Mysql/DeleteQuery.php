<?php


namespace Nwsorm\QueryWriter\Mysql;


use Nwsorm\Cache\CacheHandlerInterface;
use Nwsorm\Driver\DriverHandlerInterface;
use Nwsorm\QueryWriter\QueryDeleteInterface;
use Nwsorm\Store;

class DeleteQuery implements QueryDeleteInterface
{
    
    private const QUERY_DELETE = 'DELETE FROM ';
    private const QUERY_WHERE  = ' WHERE ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    private array                  $query_metadata = array();
    
    
    public function getQuery( string $classname, object $object ): \PDOStatement
    {
        if( $this->cacheHandler->hasDeleteQuery( $classname ) ) {
            $statement = $this->driverHandler->getConnection()->prepare(
                $this->cacheHandler->getDeleteQuery( $classname ) . $this->queryWhere( $object ) . ';'
            );
            
            $this->query_metadata = $this->cacheHandler->getDeleteMetadataQuery( $classname );
            
            return $statement;
        }
        
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
    
    
    public function getQueryMetadata(): array
    {
        return $this->query_metadata;
    }
}
