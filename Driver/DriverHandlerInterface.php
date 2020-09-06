<?php


namespace Nomess\Component\Orm\Driver;


interface DriverHandlerInterface
{
    
    public function getConnection(): \PDO;
    
    
    public function getDatabase(): string;
}
