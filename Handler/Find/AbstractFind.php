<?php


namespace Nomess\Component\Orm\Handler\Find;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryFreeInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinRelationInterface;
use Nomess\Component\Orm\Store;
use PDOStatement;

/**
 * @author Romain Lavabre
 */
abstract class AbstractFind
{
    
    protected CacheHandlerInterface      $cacheHandler;
    protected QueryFreeInterface         $queryFree;
    protected QueryJoinRelationInterface $queryJoinRelation;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        QueryFreeInterface $queryFree,
        QueryJoinRelationInterface $queryJoinRelation )
    {
        $this->cacheHandler      = $cacheHandler;
        $this->queryFree         = $queryFree;
        $this->queryJoinRelation = $queryJoinRelation;
    }
    
    
    /**
     * Set properties of object
     *
     * @param object $object
     * @param array $data
     */
    protected function setObject( object $object, array $data ): void
    {
        $classname = get_class( $object );
        
        foreach( $data as $index => $value ) {
            if( property_exists( $classname, $index ) ) {
                $reflectionProperty = Store::getReflection( $classname, $index );
                
                if( $reflectionProperty->getType()->getName() === 'array' ) {
                    if( !is_string( $value ) ) {
                        $value = array();
                    } elseif( $this->cacheHandler->getCache( get_class( $object ) )[CacheHandlerInterface::ENTITY_METADATA][$reflectionProperty->getName()][CacheHandlerInterface::ENTITY_COLUMN_TYPE] === 'JSON' ) {
                        $value = json_decode( $value, TRUE );
                    } else {
                        $value = unserialize( $value );
                    }
                }
                
                $reflectionProperty->setValue( $object, $value );
            }
        }
    }
    
    
    /**
     * Travel the cache of object, for all property that be a relations,
     * set property excepted if this class is in lazy loadinf mode
     *
     * @param object $target
     */
    protected function setRelations( object $target ): void
    {
        $classname = get_class( $target );
        $cache     = $this->cacheHandler->getCache( $classname );
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                $relationClassname = $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME];
                
                if( $this->isLazyLoaded( $relationClassname ) ) {
                    continue;
                }
                
                $statement = $this->queryJoinRelation->getQuery( $propertyName, $target );
                
                $this->fill( $relationClassname, $statement, $target, $propertyName );
            }
        }
    }
    
    
    /**
     * Get the object associated to this property
     * Relaunch the setter of relations for new value
     * Add result relations in target property's class
     *
     * @param string $classname       Classname of target relation
     * @param PDOStatement $statement Prepared request
     * @param object $object          Holder object
     * @param string $propertyName    The property's name of holder object
     */
    protected function fill( string $classname, PDOStatement $statement, object $object, string $propertyName ): void
    {
        Store::addToRepository( $object );
        $statement->execute();
        
        $relations = array();
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            $class = NULL;
            
            if( !Store::repositoryHas( $classname, $data['id'] ) ) {
                $this->setObject( $class = new $classname(), $data );
                Store::addToRepository( $class );
                $this->setRelations( $class );
            } else {
                $class = Store::getOfRepository( $classname, $data['id'] );
            }
            
            $relations[] = $class;
        }
        
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            $reflectionProperty->setValue( $object, $relations );
        } else {
            $reflectionProperty->setValue( $object, ( !empty( $relations ) ) ? $relations[0] : NULL );
        }
    }
    
    
    /**
     * Return null if date is empty
     * Return a array if {idOrSql} is empty or string (string = unknow searched)
     * Return a object if {idOrSql} is int (int = id)
     *
     * @param string|int|null $idOrSql User request
     * @param array|object|null $data  Result of request
     * @return object|array|null
     */
    protected function returnData( $idOrSql, $data )
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
     * This class is in lazy loading mode
     *
     * @param string $classname
     * @return bool
     */
    abstract protected function isLazyLoaded( string $classname ): bool;
}
