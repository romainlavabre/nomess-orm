<?php


namespace Newwebsouth\Orm\QueryWriter;


interface QueryDeleteInterface
{
    
    public function getQuery( string $classname, object $object ): \PDOStatement;
}
