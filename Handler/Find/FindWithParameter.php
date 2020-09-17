<?php


namespace Nomess\Component\Orm\Handler\Find;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryFreeInterface;
use Nomess\Component\Orm\QueryWriter\QuerySelectInterface;
use Nomess\Component\Orm\Store;
use PDOStatement;

class FindWithParameter
{
    
    private QuerySelectInterface  $querySelect;
    private QueryFreeInterface    $queryFree;
    private CacheHandlerInterface $cacheHandler;
    
    
    public function __construct(
        QuerySelectInterface $querySelect,
        QueryFreeInterface $queryFree,
        CacheHandlerInterface $cacheHandler )
    {
        $this->querySelect  = $querySelect;
        $this->queryFree    = $queryFree;
        $this->cacheHandler = $cacheHandler;
    }
    
    
    public function find( string $classname, $idOrSql, array $parameters, ?string $lock_type )
    {
        
        if( Store::repositoryHas( $classname, (int)$idOrSql ) ) {
            return Store::getOfRepository( $classname, $idOrSql );
        }
        
        $statement = $this->querySelect->getQuery( $classname, $idOrSql, $parameters, $lock_type );
        $statement->execute();
        
        $result = array();
        
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            $object = NULL;
            if( !Store::repositoryHas( $classname, $data['id'] ) ) {
                $this->setObject( $object = new $classname(), $data );
                
                Store::addToRepository( $object );
                $this->execute( $classname, $object );
            } else {
                $object = Store::getOfRepository( $classname, $data['id'] );
            }
            $result[] = $object;
        }
        
        return $this->returnData( $idOrSql, $result );
    }
    
    
    /**
     * Travel object for set relation property
     *
     * @param string $classname
     * @param object $target
     */
    private function execute( string $classname, object $target ): void
    {
        $cache           = $this->cacheHandler->getCache( $classname );
        $targetClassname = get_class( $target );
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                $statement = $this->queryFree->getQuery( $this->getJoinCondition(
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME],
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE],
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE],
                    $targetClassname,
                    Store::getReflection( $targetClassname, 'id' )->getValue( $target )
                ) );
                
                $this->fill( $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME], $statement, $target, $propertyName );
            }
        }
    }
    
    
    /**
     * Set relation property and launch travel of result
     *
     * @param string $classname
     * @param PDOStatement $statement
     * @param object $object
     * @param string $propertyName
     */
    private function fill( string $classname, PDOStatement $statement, object $object, string $propertyName ): void
    {
        Store::addToRepository( $object );
        $statement->execute();
        
        $result      = $statement->fetchAll( \PDO::FETCH_ASSOC );
        $resultValue = array();
        foreach( $result as $data ) {
            $class = NULL;
            
            if( !Store::repositoryHas( $classname, $data['id'] ) ) {
                $this->setObject( $class = new $classname(), $data );
                Store::addToRepository( $class );
                $this->execute( $classname, $class );
            } else {
                $class = Store::getOfRepository( $classname, $data['id'] );
            }
            
            $resultValue[] = $class;
        }
        
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            $reflectionProperty->setValue( $object, $resultValue );
        } else {
            if( !empty( $resultValue ) ) {
                $reflectionProperty->setValue( $object, $resultValue[0] );
            } else {
                $reflectionProperty->setValue( $object, NULL );
            }
        }
    }
    
    
    /**
     * Return a join condition
     *
     * @param string $classnameTarget
     * @param string $relationType
     * @param string|null $relationTable
     * @param string $classnameHolder
     * @param int $holderId
     * @return string
     */
    private function getJoinCondition( string $classnameTarget, string $relationType, ?string $relationTable, string $classnameHolder, int $holderId ): string
    {
        $targetCache = $this->cacheHandler->getCache( $classnameTarget );
        $holderCache = $this->cacheHandler->getCache( $classnameHolder );
        $holderTable = $holderCache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $targetTable = $targetCache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        
        $query = 'SELECT T.* FROM `' . $targetTable . '` T ';
        
        if( $relationType === 'OneToOne' ) {
            $query .= 'WHERE T.' . $holderTable . '_id = ' . $holderId;
        } elseif( $relationType === 'OneToMany' ) {
            
            $query .= 'LEFT JOIN `' . $holderTable . '` J ON J.id = ' . $holderId . ' WHERE J.' . $targetTable . '_id = T.id';
        } elseif( $relationType === 'ManyToOne' ) {
            $query .= ' WHERE T.' . $holderTable . '_id = ' . $holderId;
        } else { // ManyToMany
            $query .= 'JOIN `' . $relationTable . '` J ON J.' . $holderTable . '_id = ' . $holderId . ' WHERE J.' . $targetTable . '_id = T.id';
        }
        
        return $query;
    }
    
    
    /**
     * Control that an array or object
     *
     * @param $idOrSql
     * @param $data
     * @return object|array|null
     */
    private function returnData( $idOrSql, $data )
    {
        if( empty( $data ) ) {
            return NULL;
        }
        
        if( preg_match( '/^[0-9]+$/', $idOrSql ) ) {
            
            return $data[0];
        }
        
        return $data;
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
}
