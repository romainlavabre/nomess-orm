<?php


namespace Nomess\Component\Orm\QueryWriter;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
interface QueryUpdateNtoNInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement|null
     */
    public function getQuery( object $object ): ?\PDOStatement;
}
