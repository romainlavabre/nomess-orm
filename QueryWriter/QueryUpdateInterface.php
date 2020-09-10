<?php


namespace Nomess\Component\Orm\QueryWriter;


use PDOStatement;

interface QueryUpdateInterface
{
    
    /**
     * @param object $object
     * @return PDOStatement
     */
    public function getQuery( object $object ): PDOStatement;
}
