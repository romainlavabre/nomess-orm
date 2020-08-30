<?php


namespace Newwebsouth\Orm\QueryWriter\Mysql;


use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Newwebsouth\Orm\QueryWriter\QueryJoinInterface;
use Newwebsouth\Orm\Store;
use PDOStatement;

class JoinQuery implements QueryJoinInterface
{
    
    private DriverHandlerInterface $driverHandler;
    private CacheHandlerInterface  $cacheHandler;
    
    
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
        
        return $this->driverHandler->getConnection()->prepare( implode( '', $this->travel( $object ) ) );
    }
    
    
    private function travel( object $object ): array
    {
        $queries     = array();
        $cacheHolder = $this->cacheHandler->getCache( get_class( $object ) );
        
        foreach( $cacheHolder[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            $cacheTarget = NULL;
            
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL
                && $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE] === 'ManyToMany' ) {
                
                foreach( $this->getValue( $object, $propertyName ) as $holded ) {
                    if( $cacheTarget === NULL ) {
                        $cacheTarget = $this->cacheHandler->getCache( get_class( $holded ) );
                    }
                    
                    $queries[] = 'DELETE FROM ' .
                                 $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] .
                                 ' WHERE ' .
                                 $cacheHolder[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] .
                                 '_id = ' . $this->getId( $object ) .
                                 ' AND ' . $cacheTarget[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id = ' .
                                 $this->getId( $holded ) . ';';
                    
                    $queries[] = 'INSERT INTO ' .
                                 $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] .
                                 ' (' . $cacheHolder[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id, ' .
                                 $cacheTarget[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id) VALUES (' .
                                 $this->getId( $object ) . ', ' . $this->getId( $holded ) . ');';
                }
            }
        }
        
        return $queries;
    }
    
    
    private function getValue( object $object, string $propertyName ): array
    {
        
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        $value = NULL;
        
        if( $reflectionProperty->isInitialized( $object ) ) {
            $value = $reflectionProperty->getValue( $object );
        }
        
        if( is_array( $value ) ) {
            return $value;
        }
        
        return [];
    }
    
    
    private function getId( object $object ): int
    {
        $reflectionProperty = Store::getReflection( get_class( $object ), 'id' );
        
        return $reflectionProperty->getValue( $object );
    }
}
