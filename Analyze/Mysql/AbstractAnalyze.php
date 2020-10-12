<?php


namespace Nomess\Component\Orm\Analyze\Mysql;

use Nomess\Component\Config\ConfigStoreInterface;
use Nomess\Component\Orm\Driver\DriverHandlerInterface;

class AbstractAnalyze
{
    
    protected DriverHandlerInterface $driverHandler;
    private ConfigStoreInterface     $configStore;
    
    
    public function __construct(
        DriverHandlerInterface $driverHandler,
        ConfigStoreInterface $configStore )
    {
        $this->driverHandler = $driverHandler;
        $this->configStore   = $configStore;
    }
    
    
    protected function directories(): array
    {
        return $this->scanRecursive( $this->configStore->get( ConfigStoreInterface::DEFAULT_NOMESS )['general']['path']['default_entity'] );
    }
    
    
    protected function getReflectionClass( string $filename, string $shortfilename ): ?\ReflectionClass
    {
        $lines = file( $filename );
        
        foreach( $lines as $line ) {
            if( strpos( $line, 'namespace' ) !== FALSE ) {
                $classname = trim( str_replace( [ 'namespace', ';' ], '', $line ) ) . '\\' . str_replace( '.php', '', $shortfilename );
                
                if( class_exists( $classname ) ) {
                    return new \ReflectionClass( $classname );
                }
            }
        }
        
        return NULL;
    }
    
    
    protected function getTimestamp(): string
    {
        return md5( time() );
    }
    
    
    private function scanRecursive( string $dir ): array
    {
        $pathDirSrc = $dir;
        
        $tabDirWait = array();
        
        $dir = $pathDirSrc;
        
        $noPass = count( explode( '/', $dir ) );
        
        do {
            $stop = FALSE;
            
            do {
                $tabGeneral = scandir( $dir );
                $dirFind    = FALSE;
                
                for( $i = 0; $i < count( $tabGeneral ); $i++ ) {
                    if( is_dir( $dir . $tabGeneral[$i] . '/' ) && $tabGeneral[$i] !== '.' && $tabGeneral[$i] !== '..' ) {
                        if( !$this->controlDir( $dir . $tabGeneral[$i] . '/', $tabDirWait ) ) {
                            $dir     = $dir . $tabGeneral[$i] . '/';
                            $dirFind = TRUE;
                            break;
                        }
                    }
                }
                
                if( !$dirFind ) {
                    $tabDirWait[] = $dir;
                    $tabEx        = explode( '/', $dir );
                    unset( $tabEx[count( $tabEx ) - 2] );
                    $dir = implode( '/', $tabEx );
                }
                
                if( count( explode( '/', $dir ) ) < $noPass ) {
                    $stop = TRUE;
                    break;
                }
            } while( $dirFind === TRUE );
        } while( $stop === FALSE );
        
        return $tabDirWait;
    }
    
    
    private function controlDir( string $path, array $tab ): bool
    {
        foreach( $tab as $value ) {
            if( $value === $path ) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
}
