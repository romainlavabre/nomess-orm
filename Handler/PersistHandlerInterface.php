<?php


namespace Newwebsouth\Orm\Handler;


interface PersistHandlerInterface
{
    
    public function handle( object $object ): void;
}
