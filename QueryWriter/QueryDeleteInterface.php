<?php


namespace Nomess\Component\Orm\QueryWriter;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
interface QueryDeleteInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement
     */
    public function getQuery( object $object ): \PDOStatement;
}
