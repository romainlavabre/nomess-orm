<?php


namespace Nomess\Component\Orm\Handler\Find;


use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\QueryWriter\QueryFreeInterface;
use Nomess\Component\Orm\QueryWriter\QueryJoinRelationInterface;
use Nomess\Component\Orm\QueryWriter\QuerySelectInterface;
use Nomess\Component\Orm\Store;

/**
 * @author Romain Lavabre
 */
class FindLazy extends AbstractFind
{
    
    private const CONF_NAME = 'orm';
    private QuerySelectInterface  $querySelect;
    private array                 $config;
    
    
    public function __construct(
        QuerySelectInterface $querySelect,
        QueryFreeInterface $queryFree,
        CacheHandlerInterface $cacheHandler,
        ConfigStoreInterface $configStore,
        QueryJoinRelationInterface $queryJoinRelation )
    {
        parent::__construct( $cacheHandler, $queryFree, $queryJoinRelation );
        $this->querySelect = $querySelect;
        $this->config      = $configStore->get( self::CONF_NAME )['lazyload'];
    }
    
    
    public function find( string $classname, $idOrSql, array $parameters, ?string $lock_type )
    {
        // If store has the object searched, return this
        if( Store::repositoryHas( $classname, (int)$idOrSql ) ) {
            return Store::getOfRepository( $classname, $idOrSql );
        }
        
        $statement = $this->querySelect->getQuery( $classname, $idOrSql, $parameters, $lock_type );
        $statement->execute();
        
        $result = array();
        
        // Travel the first request's result
        foreach( $statement->fetchAll( \PDO::FETCH_ASSOC ) as $data ) {
            $object = NULL;
            // If store hasn't this object, hydrate object and set the relations
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
        return $this->config['enable']
               && ( !is_array( $this->config['exclude'] )
                    || array_search( $classname, $this->config['exclude'] ) === FALSE );
    }
}
