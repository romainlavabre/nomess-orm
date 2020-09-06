<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Store;

class DeleteHandler implements DeleteHandlerInterface
{
    
    public function handle( object $object ): void
    {
        Store::addToDelete( $object );
    }
}
