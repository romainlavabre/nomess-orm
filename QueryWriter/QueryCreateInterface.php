<?php


namespace Newwebsouth\Orm\QueryWriter;


interface QueryCreateInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement
     */
    public function getQuery( object $object ): \PDOStatement;
}
