<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Driver\DriverHandlerInterface;
use Nomess\Component\Orm\Exception\ORMException;
use Nomess\Component\Orm\Handler\Dispatcher\DispatcherHandler;
use Nomess\Component\Orm\QueryWriter\QueryCreateInterface;
use Nomess\Component\Orm\QueryWriter\QueryDeleteInterface;
use Nomess\Component\Orm\QueryWriter\QueryUpdateInterface;
use Nomess\Component\Orm\QueryWriter\QueryUpdateNtoNInterface;
use Nomess\Component\Orm\Store;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class SaveHandler implements SaveHandlerInterface
{
    
    private DispatcherHandler        $dispatcher;
    private DriverHandlerInterface   $driverHandler;
    private QueryCreateInterface     $queryCreate;
    private QueryUpdateInterface     $queryUpdate;
    private QueryDeleteInterface     $queryDelete;
    private QueryUpdateNtoNInterface $queryUpdateNtoN;
    private array                    $toJoin = array();
    
    
    public function __construct(
        DispatcherHandler $dispatcher,
        DriverHandlerInterface $driverHandler,
        QueryCreateInterface $queryCreate,
        QueryUpdateInterface $queryUpdate,
        QueryDeleteInterface $queryDelete,
        QueryUpdateNtoNInterface $queryUpdateNtoN )
    {
        $this->dispatcher      = $dispatcher;
        $this->driverHandler   = $driverHandler;
        $this->queryCreate     = $queryCreate;
        $this->queryUpdate     = $queryUpdate;
        $this->queryDelete     = $queryDelete;
        $this->queryUpdateNtoN = $queryUpdateNtoN;
    }
    
    
    /**
     * @return bool
     * @throws ORMException
     */
    public function handle(): bool
    {
        
        $this->dispatcher->dispatch();
        $connection = $this->driverHandler->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            foreach( Store::getToDelete() as $classname => &$array ) {
                foreach( $array as &$object ) {
                    $this->queryDelete->getQuery( $object )->execute();
                }
            }
            foreach( Store::getToCreate() as $classname => &$array ) {
                foreach( $array as &$object ) {
                    $this->queryCreate->getQuery( $object )->execute();
                    
                    Store::getReflection( get_class( $object ), 'id' )->setValue( $object, $connection->lastInsertId() );
                }
            }
            
            foreach( Store::getToUpdate() as $classname => &$array ) {
                foreach( $array as &$object ) {
                    $this->queryUpdate->getQuery( $object )->execute();
                    $this->toJoin[] = $object;
                }
            }
            
            $connection->commit();
            
            foreach( $this->toJoin as $object ) {
                $statement = $this->queryUpdateNtoN->getQuery( $object );
                
                if( $statement !== NULL ) {
                    $statement->execute();
                }
            }
            
            Store::resetDeleteRepository();
            Store::resetCreateRepository();
            Store::resetUpdateRepository();
            $this->toJoin = array();
        } catch( \Throwable $th ) {
            if( $connection->inTransaction() ) {
                $connection->rollBack();
            }
            
            if( NOMESS_CONTEXT === 'DEV' ) {
                throw new ORMException( $th->getMessage() . ' in ' . $th->getFile() . ' line ' . $th->getLine() );
            }
            
            return FALSE;
        }
        
        return TRUE;
    }
}
