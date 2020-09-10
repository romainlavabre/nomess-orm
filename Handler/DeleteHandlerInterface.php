<?php


namespace Nomess\Component\Orm\Handler;


interface DeleteHandlerInterface
{
    
    public function handle( object $object ): void;
}
