<?php


namespace Nomess\Component\Orm\Handler;


/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
interface StoreHandlerInterface
{
    
    /**
     * Return true if repository has this entity
     *
     * @param string $classname
     * @param int $id
     * @return bool
     */
    public function has( string $classname, int $id ): bool;
    
    
    /**
     * If repository has this entity, the repository will not be updated and will return false
     *
     * @param object $object
     * @return bool
     */
    public function set( object $object ): bool;
    
    
    /**
     * Return this entity if she's in store
     *
     * @param string $classname
     * @param int $id
     * @return object|null
     */
    public function get( string $classname, int $id ): ?object;
}
