<?php


namespace Nomess\Component\Orm\Analyze\PostgreSql;

class Table extends AbstractAnalyze
{
    
    public function revalideTables(): void
    {
        foreach( $this->directories() as $directory ) {
            foreach( scandir( $directory ) as $file ) {
                
                if( $file !== '.' && $file !== '..' && $file !== '.gitkeep'
                    && ( $reflectionClass = $this->getReflectionClass( $directory . $file, $file ) ) !== NULL
                    && $reflectionClass->isInstantiable() ) {
                    
                    $this->createTable( $reflectionClass );
                }
            }
        }
    }
    
    
    private function createTable( \ReflectionClass $reflectionClass ): void
    {
        echo "Create table " . mb_strtolower( str_replace( [ '_', '-' ], '', $reflectionClass->getShortName() ) ) . " if not exists\n";
        
        $this->driverHandler->getConnection()
                            ->query( 'CREATE TABLE IF NOT EXISTS "' .
                                     mb_strtolower( str_replace( [ '_', '-' ], '', $reflectionClass->getShortName() ) ) .
                                     '"
                                     (
                                        "id" SERIAL,
                                        PRIMARY KEY ("id")
                                     );' )->execute();
    }
}
