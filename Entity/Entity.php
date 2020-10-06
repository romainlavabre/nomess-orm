<?php


namespace Nomess\Component\Orm\Entity;


use Nomess\Component\Orm\Annotation\StateLess;
use Nomess\Component\Orm\Handler\Find\FindRelation;
use Nomess\Component\Orm\Store;
use Nomess\Container\Container;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
abstract class Entity
{
    
    /**
     * @StateLess()
     */
    private array $loaded = [];
    /**
     * @StateLess()
     */
    private bool $isStored;
    
    
    /**
     * Load the value of property when she's called
     *
     * @param string $propertyName
     * @return $this
     */
    protected final function load( string $propertyName ): self
    {
        if( $this->isPropertyLoaded( $propertyName ) ) {
            return $this;
        }
        
        Container::getInstance()->get( FindRelation::class )->load( $this, $propertyName );
        
        $this->loaded[] = $propertyName;
        
        return $this;
    }
    
    
    /**
     * Set the target with this instance
     *
     * @param object|null $target  Object where add this instance
     * @param string $propertyName The property of current setter
     * @return $this
     */
    protected final function addRelation( ?object $target, string $propertyName ): self
    {
        if( $target === NULL ) {
            return $this;
        }
        
        /** @var RelationHandler $relationHandler */
        $relationHandler = Container::getInstance()->get( RelationHandler::class );
        
        $relationHandler->set( $this, $target, $propertyName );
        
        return $this;
    }
    
    
    /**
     * @param object|null $target  Object where add this instance
     * @param string $propertyName The property of current setter
     * @return $this
     */
    protected final function removeRelation( ?object $target, string $propertyName ): self
    {
        if( $target === NULL ) {
            return $this;
        }
        
        /** @var RelationHandler $relationHandler */
        $relationHandler = Container::getInstance()->get( RelationHandler::class );
        
        $relationHandler->remove( $this, $target, $propertyName );
        
        return $this;
    }
    
    
    private function isStored(): bool
    {
        if( !isset( $this->isStored ) ) {
            $this->isStored = Store::getReflection( get_class( $this ), 'id' )->isInitialized( $this )
                              && Store::getReflection( get_class( $this ), 'id' )->getValue( $this ) > 0;
        }
        
        return $this->isStored;
    }
    
    
    /**
     * @param string $propertyName
     * @return bool
     * @internal
     */
    public final function isPropertyLoaded( string $propertyName ): bool
    {
        if( !$this->isStored() ) {
            return TRUE;
        }
        
        return in_array( $propertyName, $this->loaded );
    }
}
