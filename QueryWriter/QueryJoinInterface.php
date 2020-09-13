<?php


namespace Nomess\Component\Orm\QueryWriter;


interface QueryJoinInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement|null
     */
    public function getQuery( object $object ): ?\PDOStatement;
}
