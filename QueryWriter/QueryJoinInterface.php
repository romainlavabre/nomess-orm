<?php


namespace Newwebsouth\Orm\QueryWriter;


interface QueryJoinInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement
     */
    public function getQuery( object $object ): \PDOStatement;
}