<?php


namespace Nomess\Component\Orm\QueryWriter\Mysql;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Store;

abstract class AbstractAlterData
{
    
    protected CacheHandlerInterface $cacheHandler;
    protected array                 $toBind = array();
    
    
    public function __construct( CacheHandlerInterface $cacheHandler )
    {
        $this->cacheHandler = $cacheHandler;
    }
    
    
    /**
     * Bind the value to request parameters
     *
     * @param \PDOStatement $statement
     * @param object $object
     */
    protected function bindValue( \PDOStatement $statement, object $object ): void
    {
        foreach( $this->toBind as $propertyName => $columnName ) {
            $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
            
            if( $reflectionProperty->isInitialized( $object ) ) {
                
                $value = $reflectionProperty->getValue( $object );
                
                if( is_object( $value ) ) {
                    $reflectionPropertyOfValue = Store::getReflection( get_class( $value ), 'id' );
                    
                    if( $reflectionPropertyOfValue->isInitialized( $value )
                        && !empty( $id = $reflectionPropertyOfValue->getValue( $value ) ) ) {
                        
                        $statement->bindValue( ':' . $columnName, $id );
                    } else {
                        $statement->bindValue( ':' . $columnName, NULL );
                    }
                } else {
                    if( is_bool( $value ) ) {
                        $value = (int)$value;
                    } elseif( is_array( $value ) ) {
                        if( $this->cacheHandler->getCache( get_class( $object ) )[CacheHandlerInterface::ENTITY_METADATA][$reflectionProperty->getName()][CacheHandlerInterface::ENTITY_COLUMN_TYPE] === 'JSON' ) {
                            $value = json_encode( $value );
                        } else {
                            
                            $value = serialize( $value );
                        }
                    }
                    
                    $statement->bindValue( ':' . $columnName, $value );
                }
            } else {
                if( $reflectionProperty->getType()->getName() === 'array' ) {
                    $value = [];
    
                    if( $this->cacheHandler->getCache( get_class( $object ) )[CacheHandlerInterface::ENTITY_METADATA][$reflectionProperty->getName()][CacheHandlerInterface::ENTITY_COLUMN_TYPE] === 'JSON' ) {
                        $value = json_encode( [] );
                    } else {
                        $value = serialize( [] );
                    }
    
                    $statement->bindValue( ':' . $columnName, $value );
                } else {
                    $statement->bindValue( ':' . $columnName, NULL );
                }
            }
        }
        
        $this->toBind = array();
    }
}
