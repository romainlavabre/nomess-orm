<?php


namespace Nomess\Component\Orm\Handler\Find;


use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryFreeInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinRelationInterface;
use Nomess\Component\Orm\QueryWriter\QuerySelectInterface;
use Nomess\Component\Orm\Store;

/**
 * Find all if this system is in fast development mode without lazy loading
 *
 * @author Romain Lavabre
 */
class FindBlock extends AbstractFind
{
    
    private QuerySelectInterface  $querySelect;
    
    
    public function __construct(
        QuerySelectInterface $querySelect,
        QueryFreeInterface $queryFree,
        CacheHandlerInterface $cacheHandler,
        QueryJoinRelationInterface $queryJoinRelation )
    {
        parent::__construct( $cacheHandler, $queryFree, $queryJoinRelation );
        $this->querySelect = $querySelect;
    }
    
    
    public function find( string $classname, $idOrSql, array $parameters, ?string $lock_type )
    {
        
        if( Store::repositoryHas( $classname, (int)$idOrSql ) ) {
            return Store::getOfRepository( $classname, $idOrSql );
        }
        
        $statement = $this->querySelect->getQuery( $classname, $idOrSql, $parameters, $lock_type );
        $statement->execute();
        
        $result = array();
        
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            $object = NULL;
            if( !Store::repositoryHas( $classname, $data['id'] ) ) {
                $this->setObject( $object = new $classname(), $data );
                
                Store::addToRepository( $object );
                $this->setRelations( $object );
            } else {
                $object = Store::getOfRepository( $classname, $data['id'] );
            }
            $result[] = $object;
        }
        
        return $this->returnData( $idOrSql, $result );
    }
    
    
    protected function isLazyLoaded( string $classname ): bool
    {
        return FALSE;
    }
}
