<?php


namespace Nomess\Component\Orm\QueryWriter;


interface QueryDeleteInterface
{
    
    public function getQuery( object $object ): \PDOStatement;
}
