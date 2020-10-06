<?php


namespace Nomess\Component\Orm\Entity;


use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 * @internal
 */
class LazyLoadHandler
{
    
    private const CONF_NAME = 'orm';
    private ConfigStoreInterface $configStore;
    
    
    public function __construct( ConfigStoreInterface $configStore )
    {
        $this->configStore = $configStore;
    }
    
    
    public function isPropertyUnloaded( object $object, string $propertyName, array $metadata ): bool
    {
        if( $this->configStore->get( self::CONF_NAME )['lazyload']['enable']
            && ( !is_array( $this->configStore->get( self::CONF_NAME )['lazyload']['exclude'] )
                 || !in_array(
                    $metadata[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME],
                    $this->configStore->get( self::CONF_NAME )['lazyload']['exclude'] ) )
        ) {
            if( ( new \ReflectionClass( $object ) )->isSubclassOf( Entity::class )
                && !$object->isPropertyLoaded( $propertyName ) ) {
                
                return TRUE;
            }
        }
        
        return FALSE;
    }
}
