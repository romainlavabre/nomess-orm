<?php


namespace Nomess\Component\Orm\QueryWriter;


interface QueryCreateInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement
     */
    public function getQuery( object $object ): \PDOStatement;
}
