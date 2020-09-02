<?php

namespace Newwebsouth\Orm\QueryWriter\Mysql;

use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Newwebsouth\Orm\QueryWriter\QueryCreateInterface;
use PDOStatement;

/**
 * TODO Manage the ManyToMany relation
 */
class CreateQuery extends AbstractAlterData implements QueryCreateInterface
{
    
    private const QUERY_INSERT = 'INSERT INTO ';
    private const QUERY_VALUES = ' VALUES ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    
    
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
        $classname = get_class( $object );
        $cache     = $this->cacheHandler->getCache( $classname );
        
        $statement = $this->driverHandler->getConnection()
                                         ->prepare(
                                             self::QUERY_INSERT .
                                             $this->queryTable( $cache ) .
                                             $this->queryColumn( $cache ) .
                                             $this->queryParameters( $cache )
                                         );
        
        $this->bindValue( $statement, $object );
        
        return $statement;
    }
    
    
    private function queryTable( array $cache ): string
    {
        return $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
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
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $value ) {
            // Exclude relations
            if($value[CacheHandlerInterface::ENTITY_RELATION] === NULL && $propertyName !== 'id') {
                $line .= '`' . $value[CacheHandlerInterface::ENTITY_COLUMN] . '`, ';
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
            if( $value[CacheHandlerInterface::ENTITY_RELATION] === NULL && $propertyName !== 'id') {
                
                $columnName = $value[CacheHandlerInterface::ENTITY_COLUMN];
                
                $this->toBind[$propertyName] = $columnName;
                $line                        .= ':' . $columnName . ', ';
            }
        }
        
        return rtrim( $line, ', ' ) . ')';
    }
}
