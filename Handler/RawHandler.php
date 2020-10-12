<?php


namespace Nomess\Component\Orm\Handler;


use Nomess\Component\Orm\Driver\DriverHandlerInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class RawHandler implements RawHandlerInterface
{
    
    private DriverHandlerInterface $driveHandler;
    
    
    public function __construct( DriverHandlerInterface $driverHandler )
    {
        $this->driveHandler = $driverHandler;
    }
    
    
    public function handle( string $query, array $parameters ): array
    {
        $statement = $this->driveHandler->getConnection()->prepare( $query );
        
        foreach( $parameters as $parameter => $value ) {
            $statement->bindValue( ":$parameter", $value );
        }
        
        $statement->execute();
        
        return $statement->fetchAll( \PDO::FETCH_ASSOC );
    }
}
