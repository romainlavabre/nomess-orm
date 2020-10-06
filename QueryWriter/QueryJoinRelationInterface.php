<?php


namespace Nomess\Component\Orm\QueryWriter;


/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
interface QueryJoinRelationInterface
{
    
    public function getQuery( string $propertyName, object $holder ): \PDOStatement;
}
