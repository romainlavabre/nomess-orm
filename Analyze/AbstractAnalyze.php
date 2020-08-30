<?php


namespace Newwebsouth\Orm\Analyze;

use Newwebsouth\Orm\Driver\DriverHandlerInterface;
use Nomess\Internal;

class AbstractAnalyze
{
    
    use Scanner;
    
    private const PATH_ENTITY = ROOT . 'src/Entities/';
    protected DriverHandlerInterface $driverHandler;
    
    
    public function __construct( DriverHandlerInterface $driverHandler )
    {
        $this->driverHandler = $driverHandler;
    }
    
    
    protected function directories(): array
    {
        return $this->scanRecursive( self::PATH_ENTITY );
    }
    
    
    protected function getReflectionClass( string $filename ): ?\ReflectionClass
    {
        $lines = file( $filename );
        
        foreach( $lines as $line ) {
            if( strpos( $line, 'namespace' ) !== FALSE ) {
                $classname = trim( str_replace( [ 'namespace', ';' ], '', $line ) );
                
                if( class_exists( $classname ) ) {
                    return new \ReflectionClass( $classname );
                }
            }
        }
        
        return NULL;
    }
}
