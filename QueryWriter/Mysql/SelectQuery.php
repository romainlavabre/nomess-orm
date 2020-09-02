<?php

namespace Newwebsouth\Orm\QueryWriter\Mysql;

use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Newwebsouth\Orm\QueryWriter\QuerySelectInterface;
use PDOStatement;

class SelectQuery implements QuerySelectInterface
{
    
    private const CACHE_TARGET = 'cache_of_target';
    private const QUERY_SELECT = 'SELECT ';
    private const QUERY_FROM   = ' FROM ';
    private const QUERY_WHERE  = ' WHERE ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    private array                  $toBind = array();
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler )
    {
        $this->cacheHandler  = $cacheHandler;
        $this->driverHandler = $driverHandler;
    }
    
    
    /**
     * @param string $classname
     * @param $idOrSql
     * @param array $parameters
     * @return PDOStatement
     */
    public function getQuery( string $classname, $idOrSql, array $parameters ): PDOStatement
    {
        $cache = $this->cacheHandler->getCache( $classname );
        
        $query = self::QUERY_SELECT . '*' .
                 $this->queryPartTableTarget( $cache ) .
                 $this->queryWhereClause( $idOrSql, $parameters, $cache ) . ';';
        
        $statement = $this->driverHandler->getConnection()->prepare( $query );
        
        $this->bindValue( $statement );
        
        return $statement;
    }
    
    
    /**
     * Return the target table
     *
     * @param array $cache
     * @return string
     */
    private function queryPartTableTarget( array $cache ): string
    {
        return self::QUERY_FROM .
               $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
    }
    
    
    /**
     * Return where clause
     *
     * @param $idOrSql
     * @param array $parameters
     * @param array $cache
     * @return string
     */
    private function queryWhereClause( $idOrSql, array $parameters, array $cache ): string
    {
        
        if( is_int( $idOrSql ) ) {
            $this->toBind['id'] = $idOrSql;
            
            return self::QUERY_WHERE . 'id = :id';
        } elseif( !empty( $idOrSql ) ) {
            $this->toBind = $parameters;
            
            return self::QUERY_WHERE . $idOrSql;
        }
        
        return '';
    }
    
    
    /**
     * Bind value for statement
     *
     * @param PDOStatement $statement
     */
    private function bindValue( PDOStatement $statement ): void
    {
        foreach( $this->toBind as $paramName => $value ) {
            $statement->bindValue( ':' . $paramName, $value );
        }
    }
}
