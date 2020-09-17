<?php


namespace Nomess\Component\Orm\Handler\Find;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\Store;

class FindAll
{
    
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    private array                  $data = array();
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler )
    {
        $this->cacheHandler  = $cacheHandler;
        $this->driverHandler = $driverHandler;
    }
    
    
    public function find( string $classname, ?int $id )
    {
        if( preg_match( '/^[0-9]+$/', $id ) && Store::repositoryHas( $classname, (int)$id ) ) {
            return Store::getOfRepository( $classname, $id );
        }
        
        $cache   = $this->cacheHandler->getCache( $classname );
        $allData = $this->getAll( $classname );
        
        if( $id !== NULL ) {
            if( array_key_exists( $id, $allData ) ) {
                $this->setObject( $object = new $classname(), $allData[$id] );
                Store::addToRepository( $object );
                $this->travel( $object, $cache );
                
                return $object;
            }
        }
        
        $array = [];
        
        foreach( $this->getAll( $classname ) as $data ) {
            $this->setObject( $object = new $classname(), $data );
            Store::addToRepository( $object );
            $this->travel( $object, $cache );
            
            $array[] = $object;
        }
        
        return $array;
    }
    
    
    private function travel( object $object, array $cache ): void
    {
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                Store::getReflection( get_class( $object ), $propertyName )->setValue( $object, $this->join( $object, $array, $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME], $propertyName ) );
            }
        }
    }
    
    
    private function join( object $object, array $cacheProperty, string $tableHolder, string $propertyName )
    {
        $relationType      = $cacheProperty[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE];
        $relationClassname = $cacheProperty[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME];
        $tableTarget       = $this->cacheHandler->getCache( $relationClassname )[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $classname         = get_class( $object );
        
        if( $relationType === 'ManyToOne' ) {
            $array = [];
            
            foreach( $this->getAll( $relationClassname, $propertyName, $tableHolder, $classname ) as $id => $data ) {
                foreach( $data as $column => $value ) {
                    if( $tableHolder . '_id' === $column ) {
                        
                        if( Store::repositoryHas( $relationClassname, $id ) ) {
                            $array[] = Store::getOfRepository( $relationClassname, $id );
                        } else {
                            $array[] = $this->find( $relationClassname, $id );
                        }
                    }
                }
            }
            
            return $array;
        } elseif( $relationType === 'OneToMany' || $relationType === 'OneToOne' ) {
            $id = $this->getAll( $classname )[Store::getReflection( get_class( $object ), 'id' )->getValue( $object )][$tableTarget . '_id'];
            
            if( array_key_exists( $id, $this->getAll( get_class( $object ), $propertyName, $tableTarget, $classname ) ) ) {
                
                if( Store::repositoryHas( $relationClassname, $id ) ) {
                    return Store::getOfRepository( $relationClassname, $id );
                } else {
                    return $this->find( $relationClassname, $id );
                }
            }
            
            return NULL;
        } elseif( $relationType === 'ManyToMany' ) {
            $array = [];
            
            foreach( $this->getAll( $relationClassname, $propertyName, $tableHolder, $classname ) as $id => $data ) {
                foreach( $data as $column => $value ) {
                    if( $tableHolder . '_id' === $column ) {
                        
                        if( Store::repositoryHas( $relationClassname, $id ) ) {
                            $array[] = Store::getOfRepository( $relationClassname, $id );
                        } else {
                            $array[] = $this->find( $relationClassname, $id );
                        }
                    }
                }
            }
            
            return $array;
        }
        
        return ( $cacheProperty[CacheHandlerInterface::ENTITY_TYPE] === 'array' ) ? [] : NULL;
    }
    
    
    /**
     * Set properties of object
     *
     * @param object $object
     * @param array $data
     */
    private function setObject( object $object, array $data ): void
    {
        $classname = get_class( $object );
        
        foreach( $data as $index => $value ) {
            if( property_exists( $classname, $index ) ) {
                $reflectionProperty = Store::getReflection( $classname, $index );
                
                if( $reflectionProperty->getType()->getName() === 'array' ) {
                    if( !is_array( $value ) ) {
                        $value = array();
                    }
                }
                
                $reflectionProperty->setValue( $object, $value );
            }
        }
    }
    
    
    private function getAll( string $classname, string $propertyName = NULL, string $tableHolder = NULL, string $classnameHolder = NULL ): array
    {
        $tableTarget  = $this->cacheHandler->getCache( $classname )[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $cache        = NULL;
        $relationType = NULL;
        
        if( $propertyName !== NULL ) {
            $cache        = $this->cacheHandler->getCache( $classnameHolder )[CacheHandlerInterface::ENTITY_METADATA][$propertyName];
            $relationType = ( $cache[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) ? $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE] : NULL;
        }
        
        $indexArray = $this->getIndexArray( $tableTarget, $tableHolder, $relationType );
        
        
        if( array_key_exists( $indexArray, $this->data ) ) {
            return $this->data[$indexArray];
        }
        
        if( $tableHolder === NULL ) {
            
            $statement = $this->driverHandler->getConnection()->prepare( 'SELECT * FROM `' . $tableTarget . '`;' );
            $statement->execute();
        } else {
            
            $tableRelation = $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE];
            
            if( $relationType === 'OneToOne' || $relationType === 'OneToMany' ) {
                $statement = $this->driverHandler->getConnection()->prepare( 'SELECT * FROM `' . $tableTarget . '` WHERE ' . $tableHolder . '_id IS NOT NULL;' );
                $statement->execute();
            } elseif( $relationType === 'ManyToOne' ) {
                $statement = $this->driverHandler->getConnection()->prepare( 'SELECT * FROM `' . $tableTarget . '` INNER JOIN ' . $tableHolder . ' J ON ' . $tableTarget . '_id IS NOT NULL;' );
                $statement->execute();
            } else {
                $statement = $this->driverHandler->getConnection()->prepare( 'SELECT * FROM `' . $tableTarget . '` T INNER JOIN ' . $tableRelation . ' J WHERE J.' . $tableTarget . '_id = T.id;' );
                $statement->execute();
            }
        }
        
        $this->data[$indexArray] = [];
        
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            $this->data[$indexArray][$data['id']] = $data;
        }
        
        return $this->data[$indexArray];
    }
    
    
    private function getIndexArray( string $table1, ?string $table2, ?string $relationType ): string
    {
        if( $table2 === NULL ) {
            return $table1;
        }
        
        if( array_key_exists( $table1 . '_' . $table2 . $relationType, $this->data ) ) {
            return $table1 . '_' . $table2 . $relationType;
        }
        
        return $table2 . '_' . $table1 . $relationType;
    }
}
