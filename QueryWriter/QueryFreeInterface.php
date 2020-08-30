<?php


namespace Newwebsouth\Orm\QueryWriter;


interface QueryFreeInterface
{
    
    public function getQuery( string $query, array $parameters = array() ): \PDOStatement;
}
