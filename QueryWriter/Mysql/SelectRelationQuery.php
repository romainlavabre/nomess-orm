<?php


namespace Nomess\Component\Orm\QueryWriter\Mysql;

use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinRelationInterface;
use Nomess\Component\Orm\Store;

/**
 * @author Romain Lavabre
 */
class SelectRelationQuery implements QueryJoinRelationInterface
{
    
    private const MANY_TO_ONE = 'ManyToOne';
    private const ONE_TO_MANY = 'OneToMany';
    private const ONE_TO_ONE  = 'OneToOne';
    private CacheHandlerInterface  $cacheHandler;
    private DriverHandlerInterface $driverHandler;
    
    
    public function __construct(
        CacheHandlerInterface $cacheHandler,
        DriverHandlerInterface $driverHandler )
    {
        $this->cacheHandler  = $cacheHandler;
        $this->driverHandler = $driverHandler;
    }
    
    
    public function getQuery( string $propertyName, object $holder ): \PDOStatement
    {
        $classname = get_class( $holder );
        $cache     = $this->cacheHandler->getCache( $classname )[CacheHandlerInterface::ENTITY_METADATA][$propertyName];
        
        $query = $this->getJoinCondition(
            $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_CLASSNAME],
            $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_TYPE],
            $cache[CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE],
            $classname,
            Store::getReflection( $classname, 'id' )->getValue( $holder ),
            $propertyName
        );
        
        return $this->driverHandler->getConnection()->prepare( $query );
    }
    
    
    /**
     * Build the join condition
     *
     * @param string $classnameTarget    Name of class to join
     * @param string $relationType       Type of relation (ex: ManyToMany|ManyToOne|OneToOne|OneToMany)
     * @param string|null $relationTable Table of relation if {relationType} is ManyToMany
     * @param string $classnameHolder    Name of current class
     * @param int $holderId              Id of holder entity
     * @param string $holderProperty     Name of property of current class
     * @return string
     */
    private function getJoinCondition(
        string $classnameTarget,
        string $relationType,
        ?string $relationTable,
        string $classnameHolder,
        int $holderId,
        string $holderProperty ): string
    {
        $targetCache = $this->cacheHandler->getCache( $classnameTarget );
        $holderCache = $this->cacheHandler->getCache( $classnameHolder );
        $holderTable = $holderCache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        $targetTable = $targetCache[CacheHandlerInterface::TABLE_METADATA][CacheHandlerInterface::TABLE_NAME];
        
        $query = 'SELECT T.* FROM `' . $targetTable . '` T ';
        
        if( $relationType === self::ONE_TO_ONE ) {
            if( !$holderCache[CacheHandlerInterface::ENTITY_METADATA][$holderProperty][CacheHandlerInterface::ENTITY_RELATION][CacheHandlerInterface::ENTITY_RELATION_OWNER] ) {
                return $query . 'WHERE T.' . $holderTable . '_id = ' . $holderId;
            }
            
            return $query . ' INNER JOIN ' . $holderTable . ' J ON J.id = ' . $holderId . ' WHERE J.' . $targetTable . '_id = T.id';
        }
        
        if( $relationType === self::ONE_TO_MANY ) {
            
            return $query . 'LEFT JOIN `' . $holderTable . '` J ON J.id = ' . $holderId . ' WHERE J.' . $targetTable . '_id = T.id';
        }
        
        if( $relationType === self::MANY_TO_ONE ) {
            
            return $query . ' WHERE T.' . $holderTable . '_id = ' . $holderId;
        }
    
        // ManyToMany
        return $query . 'INNER JOIN `' . $relationTable . '` J ON J.' . $holderTable . '_id = ' . $holderId . ' WHERE J.' . $targetTable . '_id = T.id';
    }
}
