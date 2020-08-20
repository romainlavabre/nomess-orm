<?php


namespace Nwsorm\QueryWriter;


use Nwsorm\Exception\ORMException;
use PDOStatement;

interface QuerySelectInterface
{
    
    public const TABLES    = 'tables';
    public const CATCHABLE = 'catchable';
    
    
    /**
     * @param string $classname
     * @param $idOrSql
     * @param array $parameters
     * @return PDOStatement
     * @throws ORMException
     */
    public function getQuery( string $classname, $idOrSql, array $parameters ): PDOStatement;
    
    
    /**
     * Return a metadata of query
     *
     * @return array
     */
    public function getQueryMetadata(): array;
}
