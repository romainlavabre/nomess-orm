<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Handler\Find\FindAll;
use Nomess\Component\Orm\Handler\Find\FindWithParameter;

class FindHandler implements FindHandlerInterface
{
    
    private FindAll            $findAll;
    private FindWithParameter  $findWithParameter;
    
    
    public function __construct(
        FindAll $findAll,
        FindWithParameter $findWithParameter )
    {
        $this->findAll           = $findAll;
        $this->findWithParameter = $findWithParameter;
    }
    
    
    /**
     * @inheritDoc
     */
    public function handle( string $classname, $idOrSql, array $parameters = [], ?string $lock_type )
    {
        if( empty( $idOrSql ) ) {
            return $this->findAll->find( $classname, $idOrSql );
        }
        
        return $this->findWithParameter->find( $classname, $idOrSql, $parameters, $lock_type );
    }
}
