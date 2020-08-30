<?php


namespace Newwebsouth\Orm\QueryWriter;


use PDOStatement;

interface QueryUpdateInterface
{
    
    /**
     * @param object $object
     * @return PDOStatement
     */
    public function getQuery( object $object ): PDOStatement;
}
