<?php

namespace Nomess\Component\Orm\QueryWriter\Mysql;

use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryCreateInterface;
use Nomess\Component\Orm\Store;
use PDOStatement;


class CreateQuery extends AbstractAlterData implements QueryCreateInterface
{
    
    private const QUERY_INSERT = 'INSERT INTO ';
    private const QUERY_VALUES = ' VALUES ';
    private DriverHandlerInterface $driverHandler;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler )
    {
        parent::__construct( $cacheHandler );
        $this->driverHandler = $driverHandler;
    }
    
    
    /**
     * @param object $object
     * @return PDOStatement
     */
    public function getQuery( object $object ): PDOStatement
    {
        
        $classname = get_class( $object );
        $cache     = $this->cacheHandler->getCache( $classname );
        
        $statement = $this->driverHandler->getConnection()
                                         ->prepare(
                                             self::QUERY_INSERT .
                                             $this->queryTable( $cache ) .
                                             $this->queryColumn( $cache ) .
                                             $this->queryParameters( $cache ) . ';'
                                         );
        $this->bindValue( $statement, $object );
        
        return $statement;
    }
    
    
    private function queryTable( array $cache ): string
    {
        return '`' . $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '`';
    }
    
    
    /**
     * Build the part of column
     *
     * @param array $cache
     * @return string
     */
    private function queryColumn( array $cache ): string
    {
        $line = ' (';
        $isAddedToUpdate = FALSE;
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $value ) {
            // Exclude relations
            if( $value[CacheHandlerInterface::ENTITY_RELATION] === NULL && $propertyName !== 'id' ) {
                $line .= '`' . $value[CacheHandlerInterface::ENTITY_COLUMN_NAME] . '`, ';
            }else{
                if($isAddedToUpdate){
                    continue;
                }
    
                $reflectionProperty = Store::getReflection( get_class($object), $propertyName);
    
                if($reflectionProperty->isInitialized($object) && !empty( $reflectionProperty->getValue($object))){
                    Store::addToUpdate($object);
                    $isAddedToUpdate = TRUE;
                }
            }
        }
        
        return rtrim( $line, ', ' ) . ')';
    }
    
    
    /**
     * Build the parameters of query and exclude the ManyTo... relations
     *
     * @param array $cache
     * @return string
     */
    private function queryParameters( array $cache ): string
    {
        $line = self::QUERY_VALUES . '(';
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $value ) {
            
            // Exclude relations
            if( $value[CacheHandlerInterface::ENTITY_RELATION] === NULL && $propertyName !== 'id' ) {
                
                $columnName = $value[CacheHandlerInterface::ENTITY_COLUMN_NAME];
                
                $this->toBind[$propertyName] = $columnName;
                $line                        .= ':' . $columnName . ', ';
            }
        }
        
        return rtrim( $line, ', ' ) . ')';
    }
}
