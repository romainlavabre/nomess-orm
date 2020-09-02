<?php


namespace Newwebsouth\Orm\Handler;


use Newwebsouth\Orm\Cache\CacheHandlerInterface;
use Newwebsouth\Orm\QueryWriter\QueryFreeInterface;
use Newwebsouth\Orm\QueryWriter\QuerySelectInterface;
use Newwebsouth\Orm\Store;
use PDOStatement;

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
            if( Store::repositoryHas( $classname, (int)$idOrSql ) ) {
                return Store::getOfRepository( $classname, $idOrSql );
            }
        }
        
        $statement = $this->querySelect->getQuery( $classname, $idOrSql, is_array($parameters) ? $parameters : []  );
        $statement->execute();
        
        $result = array();
        
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            $this->setObject($object = new $classname(), $data);
            $this->execute( $classname, $object );
            $result[] = $object;
        }
        
        return $this->returnData( $idOrSql, $result );
    }
    
    
    private function execute( string $classname, object $target ): void
    {
        $cache = $this->cacheHandler->getCache( $classname );
        
        foreach( $cache[CacheHandlerInterface::ENTITY_METADATA] as $propertyName => $array ) {
            if( $array[CacheHandlerInterface::ENTITY_RELATION] !== NULL ) {
                $statement = $this->queryFree->getQuery( $this->getJoinCondition(
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME],
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE],
                    $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE],
                    get_class( $target ),
                    Store::getReflection( get_class( $target ), 'id' )->getValue( $target )
                ) );
                
                $this->fill( $array[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME], $statement, $target, $propertyName );
            }
        }
    }
    
    
    private function fill( string $classname, PDOStatement $statement, object $object, string $propertyName ): void
    {
        $statement->execute();
        $result             = $statement->fetchAll( \PDO::FETCH_ASSOC );
        $resultValue = array();
        
        foreach($result as $data){
            $this->setObject($class = new $classname(), $data);
            
            $resultValue[] = $class;
        }
        $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
        
        if( $reflectionProperty->getType()->getName() === 'array' ) {
            $reflectionProperty->setValue( $object, $resultValue );
        } else {
            if( !empty( $result ) ) {
                $reflectionProperty->setValue( $object, $resultValue[0] );
            } else {
                $reflectionProperty->setValue( $object, NULL );
            }
        }
        
        foreach( $resultValue as $object ) {
            $this->execute( $classname, $object );
        }
    }
    
    
    private function getJoinCondition( string $classnameTarget, string $relationType, ?string $relationTable, string $classnameHolder, int $holderId ): string
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
    
    
    /**
     * @param $idOrSql
     * @param $data
     * @return object|array|null
     */
    private function returnData( $idOrSql, $data )
    {
        
        if( empty( $data ) ) {
            return NULL;
        }
        
        if( preg_match( '/[0-9]+/', $idOrSql ) ) {
            
            return $data[0];
        }
        
        return $data;
    }
    
    private function setObject(object $object, array $data): void
    {
        foreach($data as $index => $value){
            try {
                $reflectionProperty = Store::getReflection( get_class( $object ), $index );
    
                if( $reflectionProperty->getType() === 'bool' ) {
                    $value = (bool)$value;
                } elseif( $reflectionProperty->getType() === 'array' ) {
                    if( !is_array( $value ) ) {
                        $value = array();
                    }
                }
                $reflectionProperty->setValue( $object, $value );
            }catch(\Throwable $th){}
        }
    }
}
