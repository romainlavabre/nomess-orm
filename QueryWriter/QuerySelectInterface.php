<?php


namespace Nwsorm\QueryWriter;


use Nwsorm\Exception\ORMException;
use PDOStatement;

interface QuerySelectInterface
{
    
    public const TABLES                      = 'tables';
    public const CATCHABLE                   = 'catchable';
    public const META_MAPPING_PREF_CLASSNAME = 'mapping_pref_classname';
    
    
    /**
     * @param string $classname
     * @param $idOrSql
     * @param array $parameters
     * @return PDOStatement
     * @throws ORMException
     */
    public function getQuery( string $classname, $idOrSql, array $parameters ): PDOStatement;
}
