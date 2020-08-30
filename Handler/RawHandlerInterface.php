<?php


namespace Nwsorm\Handler;


interface RawHandlerInterface
{
    
    public function handle( string $query, array $parameters = NULL );
}
