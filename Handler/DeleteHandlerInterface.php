<?php


namespace Newwebsouth\Orm\Handler;


interface DeleteHandlerInterface
{
    
    public function handle( object $object ): void;
}
