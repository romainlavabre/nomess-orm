<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Store;
use ReflectionException;

class PersistHandler implements PersistHandlerInterface
{
    
    public function handle( object $object ): void
    {
        
        if( $this->isNewInstance( $object ) ) {
            Store::addToCreate( $object );
            return;
        }
        
        Store::addToUpdate( $object );
    }
    
    
    /**
     * @param object $object
     * @return bool
     * @throws ReflectionException
     */
    private function isNewInstance( object $object ): bool
    {
        $value              = 0;
        $reflectionProperty = new \ReflectionProperty( get_class( $object ), 'id' );
        
        if( !$reflectionProperty->isPublic() ) {
            $reflectionProperty->setAccessible( TRUE );
        }
        
        if( $reflectionProperty->isInitialized( $object ) ) {
            $value = $reflectionProperty->getValue( $object );
        }
        
        return $value === 0;
    }
}
