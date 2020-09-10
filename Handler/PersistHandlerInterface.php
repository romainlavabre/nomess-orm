<?php


namespace Nomess\Component\Orm\Handler;


interface PersistHandlerInterface
{
    
    public function handle( object $object ): void;
}
