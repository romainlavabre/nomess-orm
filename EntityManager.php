<?php


namespace Nomess\Component\Orm;


use Nomess\Component\Orm\Handler\DeleteHandlerInterface;
use Nomess\Component\Orm\Handler\FindHandlerInterface;
use Nomess\Component\Orm\Handler\PersistHandlerInterface;
use Nomess\Component\Orm\Handler\RawHandlerInterface;
use Nomess\Component\Orm\Handler\SaveHandlerInterface;
use Nomess\Component\Orm\Handler\StoreHandlerInterface;
use Nomess\Container\Container;

class EntityManager implements EntityManagerInterface, TransactionSubjectInterface
{
    
    private FindHandlerInterface    $findHandler;
    private PersistHandlerInterface $persistHandler;
    private DeleteHandlerInterface  $deleteHandler;
    private SaveHandlerInterface    $saveHandler;
    private RawHandlerInterface     $rawHandler;
    private StoreHandlerInterface   $storeHandler;
    /**
     * @var TransactionObserverInterface[]
     */
    private array $subscriber = array();
    
    
    public function find( string $classname, $idOrSql = NULL, array $parameter = [], string $lock_type = NULL )
    {
        if( !isset( $this->findHandler ) ) {
            $this->findHandler = Container::getInstance()->get( FindHandlerInterface::class );
        }
        
        return $this->findHandler->handle( $classname, $idOrSql, $parameter, $lock_type );
    }
    
    
    public function persist( object $object ): EntityManagerInterface
    {
        if( !isset( $this->persistHandler ) ) {
            $this->persistHandler = Container::getInstance()->get( PersistHandlerInterface::class );
        }
        
        $this->persistHandler->handle( $object );
        
        return $this;
    }
    
    
    public function delete( object $object ): EntityManagerInterface
    {
        if( !isset( $this->deleteHandler ) ) {
            $this->deleteHandler = Container::getInstance()->get( DeleteHandlerInterface::class );
        }
        
        $this->deleteHandler->handle( $object );
        
        return $this;
    }
    
    
    public function save(): bool
    {
        if(!isset( $this->saveHandler)){
            $this->saveHandler = Container::getInstance()->get( SaveHandlerInterface::class);
        }
        
        $this->notifySubscriber( $status = $this->saveHandler->handle() );
        
        return $status;
    }
    
    
    public function addSubscriber( object $subscriber ): void
    {
        $this->subscriber[] = $subscriber;
    }
    
    
    /**
     * @inheritDoc
     */
    public function raw( string $query, array $parameters = [] ): array
    {
        if( !isset( $this->rawHandler ) ) {
            $this->rawHandler = Container::getInstance()->get( RawHandlerInterface::class );
        }
        
        return $this->rawHandler->handle( $query, $parameters );
    }
    
    
    /**
     * @inheritDoc
     */
    public function getStore( object $object ): StoreHandlerInterface
    {
        if( isset( $this->storeHandler ) ) {
            $this->storeHandler = Container::getInstance()->get( StoreHandlerInterface::class );
        }
        
        return $this->storeHandler;
    }
    
    
    /**
     * @inheritDoc
     */
    public function notifySubscriber( bool $status ): void
    {
        foreach( $this->subscriber as $subscriber ) {
            $subscriber->statusTransactionNotified( $status );
        }
    }
}
