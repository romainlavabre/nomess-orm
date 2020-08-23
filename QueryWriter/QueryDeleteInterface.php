<?php


namespace Nwsorm\QueryWriter;


interface QueryDeleteInterface
{
    
    public function getQueryMetadata(): array;
    
    
    public function getQuery( string $classname, object $object ): \PDOStatement;
}
