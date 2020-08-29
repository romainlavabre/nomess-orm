<?php


namespace Nwsorm\QueryWriter;


interface QueryFreeInterface
{
    
    public function getQuery( string $query, array $parameters = array() ): \PDOStatement;
}
