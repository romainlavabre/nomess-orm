<?php


namespace Newwebsouth\Orm\QueryWriter;


interface QueryDeleteInterface
{
    
    public function getQuery( object $object ): \PDOStatement;
}
