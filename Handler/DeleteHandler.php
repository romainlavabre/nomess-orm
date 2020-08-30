<?php


namespace Newwebsouth\Orm\Handler;


use Newwebsouth\Orm\Store;

class DeleteHandler implements DeleteHandlerInterface
{
    
    public function handle( object $object ): void
    {
        Store::addToDelete( $object );
    }
}
