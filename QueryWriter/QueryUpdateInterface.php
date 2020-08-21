<?php


namespace Nwsorm\QueryWriter;


use PDOStatement;

interface QueryUpdateInterface
{
    
    /**
     * @param object $object
     * @return PDOStatement
     */
    public function getQuery( object $object ): PDOStatement;
    
    
    public function getQueryMetadata(): array;
}
