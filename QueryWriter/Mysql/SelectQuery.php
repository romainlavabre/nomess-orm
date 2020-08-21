<?php

namespace Nwsorm\QueryWriter\Mysql;

use Nwsorm\Cache\CacheHandlerInterface;
use Nwsorm\Driver\DriverHandlerInterface;
use Nwsorm\Exception\ORMException;
use Nwsorm\QueryWriter\QuerySelectInterface;
use PDOStatement;

/**
 * TODO Make metadata
 */
class SelectQuery implements QuerySelectInterface
{
    
    private const CACHE_TARGET  = 'cache_of_target';
    private const CACHE_HOLDER  = 'cache_of_holder';
    private const RELATION_TYPE = 'relation_type';
    private const RELATED       = 'related';
    private const QUERY_SELECT  = 'SELECT ';
    private const QUERY_FROM    = ' FROM ';
    private const QUERY_JOIN    = ' LEFT JOIN ';
    private const QUERY_ON      = ' ON ';
    private const QUERY_WHERE   = ' WHERE ';
    private const QUERY_EQUAL   = ' = ';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    private array                  $relationFollowed = array();
    private array                  $toBind           = array();
    private array                  $query_metadata   = array();
    
    
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
     * @throws ORMException
     */
    public function getQuery( string $classname, $idOrSql, array $parameters ): PDOStatement
    {
        $cache = $this->cacheHandler->getCache( $classname );
        
        if( $this->cacheHandler->hasSelectQuery( $classname ) ) {
            $this->query_metadata = $this->cacheHandler->getSelectMetadataQuery( $classname );
            $statement            = $this->driverHandler->getConnection()
                                                        ->prepare(
                                                            $this->cacheHandler->getSelectQuery( $classname ) . $this->queryWhereClause( $idOrSql, $parameters, $cache )
                                                        );
            
            $this->bindValue( $statement );
            
            return $statement;
        }
        
        $query = self::QUERY_SELECT .
                 $this->queryPartColumnForRelation(
                     $this->queryPartColumn( $cache )
                 ) .
                 $this->queryPartTableTarget( $cache ) .
                 $this->queryJoin() .
                 $this->queryWhereClause( $idOrSql, $parameters, $cache ) . ';';
        
        $statement = $this->driverHandler->getConnection()->prepare( $query );
        
        $this->bindValue( $statement );
        
        return $statement;
    }
    
    
    /**
     * Return all column to select
     * If a column is relation, she's put on hold, and treat after
     *
     * @param array $cache
     * @return string
     */
    private function queryPartColumn( array $cache ): string
    {
        $line = ' ';
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            
            if( $array[CacheHandlerInterface::ENTITY_RELATION] === NULL ) {
                $line .= $array[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX] . '.' .
                         $array[CacheHandlerInterface::ENTITY_COLUMN] . ', ';
            } else {
                // Relation in waiting list
                $relationClassname = $array[CacheHandlerInterface::ENTITY_RELATION_CLASSNAME];
                
                if( !array_key_exists( $relationClassname, $this->relationFollowed ) ) {
                    $relationCache[self::CACHE_TARGET]          = $this->cacheHandler->getCache( $relationClassname );
                    $relationCache[self::CACHE_HOLDER]          = $cache;
                    $relationCache[self::RELATION_TYPE]         = $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE];
                    $relationCache[self::RELATED]               = $array[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX] . '.' . $array[CacheHandlerInterface::ENTITY_COLUMN];
                    $this->relationFollowed[$relationClassname] = $relationCache;
                }
            }
        }
        
        return $line;
    }
    
    
    /**
     * Build the column of relations
     *
     * @param string $line
     * @return string
     */
    private function queryPartColumnForRelation( string $line ): string
    {
        foreach( $this->relationFollowed as &$relation ) {
            $line .= $this->queryPartColumn( $relation );
        }
        
        reset( $this->relationFollowed );
        
        return rtrim( $line, ", " );
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
               $cache[self::CACHE_TARGET][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . ' ' .
               $cache[self::CACHE_TARGET][CacheHandlerInterface::TABLE_PREFIX];
    }
    
    
    /**
     * @return string
     * @throws ORMException
     */
    private function queryJoin(): string
    {
        $line = '';
        
        foreach( $this->relationFollowed as $classname => $relation ) {
            
            // If relation type is ManyToMany, it's a specific join
            if( $relation[self::RELATION_TYPE] === 'ManyToMany' ) {
                $line .= self::QUERY_JOIN . (
                    $relTableName = $this->getManyToManyTableName(
                        $relation[self::CACHE_TARGET][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME],
                        $relation[self::CACHE_HOLDER][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME]
                    )
                    ) . self::QUERY_ON .
                         $relation[self::CACHE_HOLDER][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX] . '' .
                         $relation[self::CACHE_HOLDER][CacheHandlerInterface::ENTITY_METADATA]['id'][CacheHandlerInterface::ENTITY_COLUMN] . self::QUERY_EQUAL .
                         $this->getPrefixRelTable( $relTableName ) . '.' .
                         $relation[self::CACHE_HOLDER][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id';
            }
            
            $line .= self::QUERY_JOIN .
                     $relation[self::CACHE_TARGET][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . ' ' .
                     $relation[self::CACHE_TARGET][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX] . self::QUERY_ON .
                     $this->queryJoinCondition( $relation );
        }
        
        return $line;
    }
    
    
    /**
     * Define condition for join tables
     *
     * @param array $cache
     * @return string
     * @throws ORMException
     */
    private function queryJoinCondition( array $cache ): string
    {
        // Define short variable for visibility
        $relationType      = $cache[self::RELATION_TYPE];
        $nameTableHolder   = $cache[self::CACHE_HOLDER][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $prefixTableHolder = $cache[self::CACHE_HOLDER][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX];
        $nameTableTarget   = $cache[self::CACHE_TARGET][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $prefixTableTarget = $cache[self::CACHE_TARGET][CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX];
        $columnIdHolder    = $cache[self::CACHE_HOLDER][CacheHandlerInterface::ENTITY_METADATA]['id'][CacheHandlerInterface::ENTITY_COLUMN];
        $columnIdTarget    = $cache[self::CACHE_TARGET][CacheHandlerInterface::ENTITY_METADATA]['id'][CacheHandlerInterface::ENTITY_COLUMN];
        
        if( $relationType === 'OneToMany' || $relationType === 'OneToOne' ) {
            // ON PTarget.id = PHolder.target_id
            return $prefixTableTarget . '.' . $columnIdTarget . self::QUERY_EQUAL . $cache[self::RELATED];
        } elseif( $relationType === 'ManyToOne' ) {
            // ON PHolder.id =  PTarget.THolder_id
            return $prefixTableHolder . '.' . $columnIdHolder . self::QUERY_EQUAL . $prefixTableTarget . '.' . $nameTableHolder . '_id';
        } elseif( $relationType === 'ManyToMany' ) {
            $nameTablePivot   = $this->getManyToManyTableName(
                $nameTableTarget,
                $nameTableHolder
            );
            $prefixTablePivot = $this->getPrefixRelTable( $nameTablePivot );
            
            // ON PTarget.id = PPivot.TTarget_id
            return $prefixTableTarget . '.' . $columnIdTarget . self::QUERY_EQUAL . $prefixTablePivot . '.' . $nameTableTarget . '_id';
        }
        
        throw new ORMException( 'An error occurred: the type of relation for table' . $nameTableTarget .
                                ' and ' . $nameTableHolder . ' not found' );
    }
    
    
    /**
     * Return the name of join table for ManyToMany relation
     *
     * @param string $table1
     * @param string $table2
     * @return string
     */
    private function getManyToManyTableName( string $table1, string $table2 ): string
    {
        $alpha = [
            1  => 'a',
            2  => 'b',
            3  => 'c',
            4  => 'd',
            5  => 'e',
            6  => 'f',
            7  => 'g',
            8  => 'h',
            9  => 'i',
            10 => 'j',
            11 => 'k',
            12 => 'l',
            13 => 'm',
            14 => 'n',
            15 => 'o',
            16 => 'p',
            17 => 'q',
            18 => 'r',
            19 => 's',
            20 => 't',
            21 => 'u',
            22 => 'v',
            23 => 'w',
            24 => 'x',
            25 => 'y',
            26 => 'z'
        ];
        
        $iteration   = strlen( $table1 ) > strlen( $table2 ) ? strlen( $table1 ) : strlen( $table2 );
        $arrayTable1 = str_split( $table1 );
        $arrayTable2 = str_split( $table2 );
        $scoreTable1 = 0;
        $scoreTable2 = 0;
        
        for( $i = 0; $i < $iteration; $i++ ) {
            if( array_key_exists( $i, $arrayTable1 ) && array_key_exists( $i, $arrayTable2 ) ) {
                if( in_array( $arrayTable1[$i], $alpha ) ) {
                    $scoreTable1 += array_search( $arrayTable1[$i], $alpha );
                }
                
                if( in_array( $arrayTable2[$i], $alpha ) ) {
                    $scoreTable2 += array_search( $arrayTable2[$i], $alpha );
                }
            } else {
                
                if( $scoreTable1 > $scoreTable2 ) {
                    return $table2 . '_' . $table1;
                } elseif( $scoreTable2 > $scoreTable1 ) {
                    return $table1 . '_' . $table2;
                }
                
                if( !array_key_exists( $i, $arrayTable1 ) ) {
                    return $table1 . '_' . $table2;
                } else {
                    return $table2 . '_' . $table1;
                }
            }
        }
        
        if( $scoreTable1 > $scoreTable2 ) {
            return $table2 . '_' . $table1;
        } else {
            return $table1 . '_' . $table2;
        }
    }
    
    
    private function getPrefixRelTable( string $tableName ): string
    {
        return mb_strtoupper( $tableName );
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
            
            return self::QUERY_WHERE . $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_PREFIX] . '.' .
                   $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME] . '_id = :id';
        } elseif( !empty( $idOrSql ) ) {
            $this->toBind = $parameters;
            
            // TODO Add prefix to column name
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
    
    
    /**
     * Return a metadata of query
     *
     * @return array
     */
    public function getQueryMetadata(): array
    {
        return $this->query_metadata;
    }
}
