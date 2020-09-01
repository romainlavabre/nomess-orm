<?php


namespace Newwebsouth\Orm\Analyze;

use Newwebsouth\Orm\Driver\DriverHandlerInterface;

class AbstractAnalyze
{
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
    
    
    protected function getReflectionClass( string $filename, string $shortfilename): ?\ReflectionClass
    {
        $lines = file( $filename );
        
        foreach( $lines as $line ) {
            if( strpos( $line, 'namespace' ) !== FALSE ) {
                $classname = trim( str_replace( [ 'namespace', ';' ], '', $line ) ) . '\\' . str_replace('.php', '', $shortfilename);
                
                if( class_exists( $classname ) ) {
                    return new \ReflectionClass( $classname );
                }
            }
        }
        
        return NULL;
    }
    
    private function scanRecursive(string $dir) : array
    {
        $pathDirSrc = $dir;
        
        $tabGeneral = scandir($pathDirSrc);
        
        $tabDirWait = array();
        
        $dir = $pathDirSrc;
        
        $noPass = count(explode('/', $dir));
        
        do{
            $stop = false;
            
            do{
                $tabGeneral = scandir($dir);
                $dirFind = false;
                
                for($i = 0; $i < count($tabGeneral); $i++){
                    if(is_dir($dir . $tabGeneral[$i] . '/') && $tabGeneral[$i] !== '.' && $tabGeneral[$i] !== '..'){
                        if(!$this->controlDir($dir . $tabGeneral[$i] . '/', $tabDirWait)){
                            $dir = $dir . $tabGeneral[$i] . '/';
                            $dirFind = true;
                            break;
                        }
                    }
                }
                
                if(!$dirFind){
                    $tabDirWait[] = $dir;
                    $tabEx = explode('/', $dir);
                    unset($tabEx[count($tabEx) - 2]);
                    $dir = implode('/', $tabEx);
                }
                
                if(count(explode('/', $dir)) < $noPass){
                    $stop = true;
                    break;
                }
            }
            while($dirFind === true);
        }
        while($stop === false);
        
        return $tabDirWait;
    }
    
    
    private function controlDir(string $path, array $tab) : bool
    {
        foreach($tab as $value){
            if($value === $path){
                return true;
            }
        }
        
        return false;
    }
}
