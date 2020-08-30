<?php


namespace Nwsorm\QueryWriter\Mysql;


use Nwsorm\Cache\CacheHandlerInterface;
use Nwsorm\Driver\DriverHandlerInterface;
use Nwsorm\QueryWriter\QueryUpdateInterface;
use Nwsorm\Store;
use PDOStatement;

class UpdateQuery extends AbstractAlterData implements QueryUpdateInterface
{
    
    private const QUERY_UPDATE = 'UPDATE ';
    private const QUERY_SET    = ' SET ';
    private const QUERY_WHERE  = ' WHERE ';
    private CacheHandlerInterface    $cacheHandler;
    private DriverHandlerInterface   $driverHandler;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler )
    {
        $this->cacheHandler  = $cacheHandler;
        $this->driverHandler = $driverHandler;
    }
    
    
    /**
     * @param object $object
     * @return PDOStatement
     */
    public function getQuery( object $object ): PDOStatement
    {
        $classname  = get_class( $object );
        $cache      = $this->cacheHandler->getCache( $classname );
        $connection = $this->driverHandler->getConnection();
        
        $statement = $connection->prepare(
            self::QUERY_UPDATE .
            $this->queryTable( $cache ) .
            self::QUERY_SET .
            $this->queryColumnParam( $cache ) .
            $this->queryWhere( $cache, $object ) .
            ';'
        );
        
        $this->bindValue( $statement, $object );
        
        return $statement;
    }
    
    
    /**
     * Return the target table
     *
     * @param array $cache
     * @return string
     */
    private function queryTable( array $cache ): string
    {
        return $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
    }
    
    
    /**
     * Return the columns and parameters
     *
     * @param array $cache
     * @return string
     */
    private function queryColumnParam( array $cache ): string
    {
        $line = '';
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            
            //Relation ManyTo... excluded
            if( $array[CacheHandlerInterface::ENTITY_RELATION] === NULL
                || strpos( $array[CacheHandlerInterface::ENTITY_RELATION_TYPE], 'OneTo' ) !== FALSE ) {
                
                $columnName = $array[CacheHandlerInterface::ENTITY_COLUMN];
                $line       .= $columnName . ' = :' . $columnName . ', ';
                
                $this->toBind[$propertyName] = $columnName;
            }
        }
        
        return rtrim( $line, ', ' );
    }
    
    
    /**
     * Return where clause
     *
     * @param array $cache
     * @param object $object
     * @return string
     */
    private function queryWhere( array $cache, object $object ): string
    {
        $reflectionProperty = Store::getReflection( get_class( $object ), 'id' );
        
        return self::QUERY_WHERE . $cache[CacheHandlerInterface::ENTITY_METADATA]['id'][CacheHandlerInterface::ENTITY_COLUMN] . '_id = \'' .
               $reflectionProperty->getValue( $object ) . '\'';
    }
}
