<?php


namespace Nwsorm\QueryWriter;


interface QueryCreateInterface
{
    
    /**
     * @param object $object
     * @return \PDOStatement
     */
    public function getQuery( object $object ): \PDOStatement;
    
    
    /**
     * Return a metadata of query
     *
     * @return array
     */
    public function getQueryMetadata(): array;
}
