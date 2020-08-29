<?php


namespace Nwsorm\QueryWriter;


interface QueryDeleteInterface
{
    
    public function getQuery( string $classname, object $object ): \PDOStatement;
    
    
    public function getQueryMetadata(): array;
}
