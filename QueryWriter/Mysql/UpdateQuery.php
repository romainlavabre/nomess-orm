<?php


namespace Nomess\Component\Orm\QueryWriter\Mysql;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\Entity\LazyLoadHandler;
use Nomess\Component\Orm\QueryWriter\QueryUpdateInterface;
use Nomess\Component\Orm\Store;
use PDOStatement;

class UpdateQuery extends AbstractAlterData implements QueryUpdateInterface
{
    
    private const QUERY_UPDATE = 'UPDATE ';
    private const QUERY_SET    = ' SET ';
    private const QUERY_WHERE  = ' WHERE ';
    private DriverHandlerInterface   $driverHandler;
    private LazyLoadHandler          $lazyloader;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler,
        LazyLoadHandler $lazyloader )
    {
        parent::__construct( $cacheHandler );
        $this->driverHandler = $driverHandler;
        $this->lazyloader    = $lazyloader;
    }
    
    
    /**
     * @param object $object
     * @return PDOStatement
     */
    public function getQuery( object $object ): PDOStatement
    {
        $this->toBind = array();
        $classname    = get_class( $object );
        $cache        = $this->cacheHandler->getCache( $classname );
        $connection   = $this->driverHandler->getConnection();
        
        $statement = $connection->prepare(
            self::QUERY_UPDATE .
            $this->queryTable( $cache ) .
            self::QUERY_SET .
            $this->queryColumnParam( $cache, $object ) .
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
        return '`' . $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '`';
    }
    
    
    /**
     * Return the columns and parameters
     *
     * @param array $cache
     * @return string
     */
    private function queryColumnParam( array $cache, object $object ): string
    {
        $line = '';
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            
            //Relation ManyTo... excluded
            if(
                (
                    $array[CacheHandlerInterface::ENTITY_RELATION] === NULL
                    || $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE] === 'OneToMany'
                    ||
                    (
                        $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE] === 'OneToOne'
                        && $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_OWNER]
                    )
                )
                && $propertyName !== 'id' ) {
                
                // If property is unloaded, pass
                if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL
                    && $this->lazyloader->isPropertyUnloaded( $object, $propertyName, $array ) ) {
                    
                    continue;
                }
                
                $columnName = NULL;
                
                if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                    $columnName = $this->cacheHandler->getCache(
                            $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME]
                        )[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id';
                } else {
                    $columnName = $array[CacheHandlerInterface::ENTITY_COLUMN_NAME];
                }
                
                $line .= '`' . $columnName . '` = :' . $columnName . ', ';
                
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
        
        return self::QUERY_WHERE . 'id = ' .
               $reflectionProperty->getValue( $object );
    }
}
