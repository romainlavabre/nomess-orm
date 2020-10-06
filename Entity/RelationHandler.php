<?php


namespace Nomess\Component\Orm\Entity;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Store;
use Nomess\Helpers\ArrayHelper;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 * @internal
 */
class RelationHandler
{
    
    use ArrayHelper;
    
    private CacheHandlerInterface $cacheHandler;
    
    
    public function __construct( CacheHandlerInterface $cacheHandler )
    {
        $this->cacheHandler = $cacheHandler;
    }
    
    
    public function set( object $holder, object $target, string $propertyName ): void
    {
        $cache             = $this->cacheHandler->getCache( get_class( $holder ) )[CacheHandlerInterface::ENTITY_METADATA][$propertyName];
        $relationClassname = $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME];
        
        $inversed = $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_INVERSED];
        
        
        if( $inversed === NULL ) {
            return;
        }
        $reflectionProperty = Store::getReflection( $relationClassname, $inversed );
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            $value = [];
            
            if( $reflectionProperty->isInitialized() ) {
                $value = $reflectionProperty->getValue( $target );
                
                if( !is_array( $value ) ) {
                    $value = [];
                }
            }
            
            if( !$this->arrayContainsValue( $holder, $value ) ) {
                $value[] = $holder;
            }
            
            $reflectionProperty->setValue( $target, $value );
            
            return;
        }
        
        if( is_object( $value = $reflectionProperty->getValue( $target ) ) ) {
            $this->remove( $target, $value, $inversed );
        }
        
        $reflectionProperty->setValue( $target, $value );
    }
    
    
    public function remove( object $holder, object $target, string $propertyName ): void
    {
        $cache             = $this->cacheHandler->getCache( get_class( $holder ) )[CacheHandlerInterface::ENTITY_METADATA][$propertyName];
        $relationClassname = $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME];
        
        $inversed = $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_INVERSED];
        
        
        if( $inversed === NULL ) {
            return;
        }
        
        $reflectionProperty = Store::getReflection( $relationClassname, $inversed );
        
        if( $reflectionProperty->isInitialized() ) {
            return;
        }
        
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            
            $value = $reflectionProperty->getValue( $target );
            
            if( !is_array( $value ) ) {
                return;
            }
            
            if( !$this->arrayContainsValue( $holder, $value ) ) {
                return;
            }
            
            unset( $value[$this->indexOf( $holder, $value )] );
            
            $reflectionProperty->setValue( $target, $value );
            
            return;
        }
        
        $reflectionProperty->setValue( $target, NULL );
    }
}
