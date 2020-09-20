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
        $result = NULL;
        
        if( empty( $idOrSql ) || preg_match( '/^[0-9]+$/', $idOrSql )) {
            $result = $this->findAll->find( $classname, $idOrSql );
        } else {
            $result = $this->findWithParameter->find( $classname, $idOrSql, $parameters, $lock_type );
        }
    
    
        if( preg_match( '/^[0-9]+$/', $idOrSql ) ) {
            if( !empty( $result ) && is_array( $result)) {
                return $result[0];
            }
        }
        
        if( !empty( $result ) ) {
            return $result;
        }
        
        return NULL;
    }
}
