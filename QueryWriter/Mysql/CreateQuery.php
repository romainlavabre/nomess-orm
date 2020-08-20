<?php

namespace Nwsorm\QueryWriter\Mysql;

use Nwsorm\Cache\CacheHandlerInterface;
use Nwsorm\Driver\DriverHandlerInterface;
use Nwsorm\QueryWriter\QueryCreateInterface;
use Nwsorm\Store;

/**
 * TODO Manage the ManyToMany relation
 * TODO Make the metadata of query
 */
class CreateQuery implements QueryCreateInterface
{
    
    private const QUERY_INSERT = 'INSERT INTO ';
    private const QUERY_VALUES = ' VALUES ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    private array                  $toBind         = array();
    private array                  $query_metadata = array();
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler )
    {
        $this->cacheHandler  = $cacheHandler;
        $this->driverHandler = $driverHandler;
    }
    
    
    /**
     * @param object $object
     * @return \PDOStatement
     */
    public function getQuery( object $object ): \PDOStatement
    {
        $classname = get_class( $object );
        $cache     = $this->cacheHandler->getCache( $classname );
        
        if( $this->cacheHandler->hasCreateQuery( $classname ) ) {
            $this->query_metadata = $this->cacheHandler->getCreateMetadataQuery( $classname );
            
            $statement = $this->driverHandler->getConnection()
                                             ->prepare(
                                                 $this->cacheHandler->getCreateQuery( $classname )
                                             );
            
            $this->bindValue( $statement, $object );
            
            return $statement;
        }
        
        $statement = $this->driverHandler->getConnection()
                                         ->prepare(
                                             self::QUERY_INSERT .
                                             $this->queryTable( $cache ) .
                                             $this->queryColumn( $cache ) .
                                             $this->queryParameters( $cache, $object )
                                         );
        
        $this->bindValue( $statement, $object );
        
        return $statement;
    }
    
    
    private function queryTable( array $cache ): string
    {
        return $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
    }
    
    
    private function queryColumn( array $cache ): string
    {
        $line = ' (';
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $value ) {
            $line .= $value[CacheHandlerInterface::ENTITY_COLUMN] . ', ';
        }
        
        return rtrim( $line, ', ' ) . ')';
    }
    
    
    private function queryParameters( array $cache, object $object ): string
    {
        $line = self::QUERY_VALUES . '(';
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $value ) {
            $columnName = $value[CacheHandlerInterface::ENTITY_COLUMN];
            
            if( $value[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                if( strpos( $value[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE], 'OneTo' ) !== FALSE ) {
                    $this->toBind[$propertyName] = $columnName;
                    $line                        .= ':' . $columnName . ', ';
                }
                
                $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
                
                $this->store( $reflectionProperty->getValue( $object ) );
            } else {
                $this->toBind[$propertyName] = $columnName;
                $line                        .= ':' . $value[CacheHandlerInterface::ENTITY_COLUMN] . ', ';
            }
        }
        
        return rtrim( $line, ', ' ) . ')';
    }
    
    
    private function bindValue( \PDOStatement $statement, object $object ): void
    {
        foreach( $this->toBind as $propertyName => $columnName ) {
            $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
            
            if( $reflectionProperty->isInitialized( $object ) ) {
                
                $value = $reflectionProperty->getValue( $object );
                
                if( is_object( $value ) ) {
                    $reflectionPropertyOfValue = Store::getReflection( get_class( $object ), 'id' );
                    
                    if( $reflectionPropertyOfValue->isInitialized( $value ) && !empty( $id = $reflectionPropertyOfValue->getValue( $object ) ) ) {
                        $statement->bindValue( ':' . $columnName, $id );
                    } else {
                        // Must be updated with new id of object value
                        Store::addToUpdate( $object );
                        $statement->bindValue( ':' . $columnName, NULL );
                    }
                } else {
                    $statement->bindValue( ':' . $columnName, $reflectionProperty->getValue( $object ) );
                }
            }
        }
    }
    
    
    /**
     * Store the objects worn by target
     *
     * @param $value
     */
    private function store( $value ): void
    {
        if( is_array( $value ) ) {
            foreach( $value as $object ) {
                $this->addInStore( $object );
            }
        } elseif( is_object( $value ) ) {
            $this->addInStore( $value );
        }
    }
    
    
    /**
     * If object has not id, this object is created, else, updated
     *
     * @param object $value
     */
    private function addInStore( object $value ): void
    {
        $reflectionProperty = Store::getReflection( get_class( $value ), 'id' );
        
        if( !$reflectionProperty->isInitialized( $value ) || is_int( $reflectionProperty->getValue( $value ) ) ) {
            Store::addToCreate( $value );
        } else {
            Store::addToUpdate( $value );
        }
    }
    
    
    /**
     * Return a metadata of query
     *
     * @return array
     */
    public function getQueryMetadata(): array
    {
        return $this->query_metadata;
    }
}
