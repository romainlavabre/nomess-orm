<?php


namespace Nomess\Component\Orm\Handler;


interface RawHandlerInterface
{
    
    public function handle( string $query, array $parameters = NULL );
}
