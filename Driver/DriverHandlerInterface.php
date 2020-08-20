<?php


namespace Nwsorm\Driver;


interface DriverHandlerInterface
{
    
    public function getConnection(): \PDO;
}
