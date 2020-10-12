<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Store;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class StoreHandler implements StoreHandlerInterface
{
    
    /**
     * @inheritDoc
     */
    public function has( string $classname, int $id ): bool
    {
        return Store::repositoryHas( $classname, $id );
    }
    
    
    /**
     * @inheritDoc
     */
    public function set( object $object ): bool
    {
        return Store::addToRepository( $object );
    }
    
    
    /**
     * @inheritDoc
     */
    public function get( string $classname, int $id ): ?object
    {
        return Store::getOfRepository( $classname, $id );
    }
}
