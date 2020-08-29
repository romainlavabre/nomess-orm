<?php


namespace Nwsorm\Handler;


interface PersistHandlerInterface
{
    
    public function handle( object $object ): void;
}
