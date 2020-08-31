<?php


namespace Newwebsouth\Orm\Driver;


interface DriverHandlerInterface
{
    
    public function getConnection(): \PDO;
    
    
    public function getDatabase(): string;
}
