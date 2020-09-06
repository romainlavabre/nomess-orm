<?php


namespace Nomess\Component\Orm\Handler\Dispatcher;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Store;

/**
 * This class reorganize the object that must be created and updated:
 * Visit and add to create or update the encapsulated object
 */
class DispatcherHandler
{
    
    private CacheHandlerInterface $cacheHandler;
    
    
    public function __construct( CacheHandlerInterface $cacheHandler )
    {
        $this->cacheHandler = $cacheHandler;
    }
    
    
    /**
     * Reorganize the repository of create and update
     */
    public function dispatch(): void
    {
        $createRepository = Store::getToCreate();
        $updateRepository = Store::getToUpdate();
        Store::resetCreateRepository();
        Store::resetUpdateRepository();
        
        foreach( $createRepository as $classname => $array ) {
            $cache = $this->cacheHandler->getCache( $classname );
            
            /** @var object $object */
            foreach( $array as $object ) {
                $this->dispatchCreate( $cache, $object );
            }
        }
        
        foreach( $updateRepository as $classname => $array ) {
            $cache = $this->cacheHandler->getCache( $classname );
            
            /** @var object $object */
            foreach( $array as $object ) {
                $this->dispatchUpdate( $cache, $object );
            }
        }
    }
    
    
    private function dispatchCreate( array $cache, object $object ): void
    {
        Store::addToCreate( $object );
        
        $this->travel( $cache, $object );
    }
    
    
    private function dispatchUpdate( array $cache, object $object ): void
    {
        Store::addToUpdate( $object );
        
        $this->travel( $cache, $object );
    }
    
    
    /**
     * Browse the object and treat the encapsulated object
     *
     * @param array $cache
     * @param object $object
     */
    private function travel( array $cache, object $object ): void
    {
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $metadata ) {
            if( $metadata[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                
                if( $metadata[CacheHandlerInterface::ENTITY_TYPE] === 'array' ) {
                    $values = $this->getValue( $object, $propertyName );
                    
                    if( is_array( $values ) ) {
                        /** @var object $value */
                        foreach( $values as $value ) {
                            
                            $this->orientDispatcher( $value );
                        }
                    }
                } else {
                    if( ( $value = $this->getValue( $object, $propertyName ) ) !== NULL ) {
                        $this->orientDispatcher( $value );
                    }
                }
            }
        }
    }
    
    
    /**
     * Manage the cycle of dispatching object
     *
     * @param object $object
     */
    private function orientDispatcher( object $object ): void
    {
        if( !Store::toDeleteHas( $object ) ) {
            $cache = $this->cacheHandler->getCache( get_class( $object ) );
            
            if( $this->isNewInstance( $object ) ) {
                if( !Store::toCreateHas( $object ) ) {
                    $this->dispatchCreate( $cache, $object );
                }
            }
    
            if( !Store::toUpdateHas( $object ) ) {
                $this->dispatchUpdate( $cache, $object );
            }
        }
    }
    
    
    /**
     * Control that object must be created or updated
     *
     * @param object $object
     * @return bool
     */
    private function isNewInstance( object $object ): bool
    {
        $value              = 0;
        $reflectionProperty = Store::getReflection(get_class($object), 'id');
        
        if( !$reflectionProperty->isPublic() ) {
            $reflectionProperty->setAccessible( TRUE );
        }
        
        if( $reflectionProperty->isInitialized( $object ) ) {
            $value = $reflectionProperty->getValue( $object );
        }
        
        return $value === 0;
    }
    
    
    /**
     * Return the value of property object
     *
     * @param object $object
     * @param string $propertyName
     * @return null|array|object
     */
    private function getValue( object $object, string $propertyName )
    {
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        if( !$reflectionProperty->isInitialized( $object ) ) {
            return NULL;
        }
        
        return $reflectionProperty->getValue( $object );
    }
}
