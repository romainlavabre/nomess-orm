<?php


namespace Nomess\Component\Orm\QueryWriter;


interface QueryFreeInterface
{
    
    public function getQuery( string $query, array $parameters = array() ): \PDOStatement;
}
