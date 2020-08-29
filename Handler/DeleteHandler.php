<?php


namespace Nwsorm\Handler;


use Nwsorm\Store;

class DeleteHandler implements DeleteHandlerInterface
{
    
    public function handle( object $object ): void
    {
        Store::addToDelete( $object );
    }
}
