<?php


namespace Nomess\Component\Orm\QueryWriter\Mysql;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinInterface;
use Nomess\Component\Orm\Store;
use PDOStatement;

class JoinQuery implements QueryJoinInterface
{
    
    private DriverHandlerInterface $driverHandler;
    private CacheHandlerInterface  $cacheHandler;
    private array                  $relationTreated = [];
    
    
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
    public function getQuery( object $object ): ?PDOStatement
    {
        $query = implode( '', $this->travel( $object ) );
        
        if( !empty( $query ) ) {
            var_dump( $query );
            
            return $this->driverHandler->getConnection()->prepare( $query );
        }
        
        return NULL;
    }
    
    
    private function travel( object $object ): array
    {
        $queries     = array();
        $cacheHolder = $this->cacheHandler->getCache( get_class( $object ) );
        
        foreach( $cacheHolder[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            $cacheTarget = NULL;
            
            $instance1 = get_class( $object ) . '::' . ( $idHolder = $this->getId( $object ) ) . '_' . $propertyName;
            
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL
                && $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE] === 'ManyToMany' ) {
                
                if( !Store::toCreateHas( $object ) ) {
                    $queries[] = 'DELETE FROM `' .
                                 $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] .
                                 '` WHERE ' .
                                 $cacheHolder[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] .
                                 '_id = ' . $idHolder . ';';
                }
                
                foreach( $this->getValue( $object, $propertyName ) as $holded ) {
                    if( $cacheTarget === NULL ) {
                        $cacheTarget = $this->cacheHandler->getCache( get_class( $holded ) );
                    }
                    
                    $instance2 = get_class( $holded ) . '::' . ( $idTarget = $this->getId( $holded ) ) . '_' . $propertyName;
                    
                    if( !$this->isTreat( $instance1, $instance2 ) ) {
                        $queries[] = 'INSERT INTO `' .
                                     $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE] .
                                     '` (' . $cacheHolder[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id, ' .
                                     $cacheTarget[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id) VALUES (' .
                                     $idHolder . ', ' . $idTarget . ');';
                    }
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
    
    
    private function isTreat( string $instance1, ?string $instance2 ): bool
    {
        return array_key_exists( $instance1 . '_' . $instance2, $this->relationTreated );
    }
}
