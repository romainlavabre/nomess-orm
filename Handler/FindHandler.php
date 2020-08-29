<?php


namespace Nwsorm\Handler;


use Nwsorm\Cache\CacheHandlerInterface;
use Nwsorm\QueryWriter\QueryFreeInterface;
use Nwsorm\QueryWriter\QuerySelectInterface;
use Nwsorm\Store;

class FindHandler implements FindHandlerInterface
{
    
    private QuerySelectInterface  $querySelect;
    private QueryFreeInterface    $queryFree;
    private CacheHandlerInterface $cacheHandler;
    
    
    public function __construct(
        QuerySelectInterface $querySelect,
        QueryFreeInterface $queryFree,
        CacheHandlerInterface $cacheHandler )
    {
        $this->querySelect  = $querySelect;
        $this->queryFree    = $queryFree;
        $this->cacheHandler = $cacheHandler;
    }
    
    
    /**
     * @inheritDoc
     */
    public function handle( string $classname, $idOrSql, array $parameters = NULL )
    {
        if( preg_match( '/[0-9]+/', $idOrSql ) ) {
            if( Store::repositoryHas( $classname, $idOrSql ) ) {
                return Store::getOfRepository( $classname, $idOrSql );
            }
        }
        // TODO remonter le block statement et target obligatoire, foreach et retour du tableau/object/null
        // TODO modifier QuerySelect
        // TODO Cache builder
        // TODO Analize et modif BDD
    }
    
    
    private function execute( string $classname, $idOrSql, array $parameters = NULL, object $target = NULL )
    {
        $cache = $this->cacheHandler->getCache( $classname );
        
        if( $target === NULL ) {
            $statement = $this->querySelect->getQuery( $classname, $idOrSql, $parameters );
            $target    = $statement->execute();
        }
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                $statement = $this->queryFree->getQuery( $this->getJoinCondition(
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME],
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE],
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE],
                    get_class( $target ),
                    Store::getReflection( get_class( $target ), 'id' )->getValue( $target )
                ) );
                
                $this->fill( $array[CacheHandlerInterface::ENTITY_RELATION_CLASSNAME], $statement, $target, $propertyName );
            }
        }
        
        return $target;
    }
    
    
    private function fill( string $classname, \PDOStatement $statement, object $object, string $propertyName ): void
    {
        $statement->execute();
        $result             = $statement->fetchAll( \PDO::FETCH_CLASS, $classname );
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            $reflectionProperty->setValue( $object, $result );
        } else {
            if( !empty( $result ) ) {
                $reflectionProperty->setValue( $object, $result[0] );
            } else {
                $reflectionProperty->setValue( $object, NULL );
            }
        }
        
        foreach( $result as $object ) {
            $this->execute( $classname, NULL, NULL, $object );
        }
    }
    
    
    private function getJoinCondition( string $classnameTarget, string $relationType, string $relationTable, string $classnameHolder, int $holderId ): string
    {
        // SELECT * FROM $classname WHERE
        $targetCache = $this->cacheHandler->getCache( $classnameTarget );
        $holderCache = $this->cacheHandler->getCache( $classnameHolder );
        
        $query = 'SELECT T.* FROM ' . $this->getQueryTable( $targetCache ) . ' T ';
        
        if( $relationType === 'OneToMany' || $relationType === 'OneToOne' ) {
            $query .= 'WHERE T.' . $this->getQueryTable( $holderCache ) . '_id = ' . $holderId;
        } elseif( $relationType === 'ManyToOne' ) {
            $query .= 'INNER JOIN ' .
                      $this->getQueryTable( $holderCache ) .
                      ' J ON J.' . $this->getQueryTable( $targetCache ) . '_id = T.id AND J.id = ' . $holderId;
        } else { // ManyToMany
            $query .= 'INNER JOIN ' . $relationTable . ' J ON J.' . $this->getQueryTable( $targetCache ) . '_id = ' . $holderId;
        }
        
        return $query;
    }
    
    
    private function getQueryTable( array $cache ): string
    {
        return $cache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
    }
}
