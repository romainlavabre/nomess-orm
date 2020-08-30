<?php


namespace Newwebsouth\Orm\QueryWriter\Mysql;


use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Newwebsouth\Orm\QueryWriter\QueryFreeInterface;

class FreeQuery implements QueryFreeInterface
{
    
    private DriverHandlerInterface $driverHandler;
    
    
    public function __construct( DriverHandlerInterface $driverHandler )
    {
        $this->driverHandler = $driverHandler;
    }
    
    
    public function getQuery( string $query, array $parameters = array() ): \PDOStatement
    {
        $statement = $this->driverHandler->getConnection()
                                         ->prepare( $query );
        
        $this->bindValue( $parameters, $statement );
        
        return $statement;
    }
    
    
    private function bindValue( array $parameters, \PDOStatement $statement ): void
    {
        foreach( $parameters as $parameterName => $value ) {
            $statement->bindValue( ":$parameters", $value );
        }
    }
}
