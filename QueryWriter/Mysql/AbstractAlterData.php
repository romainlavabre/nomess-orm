<?php


namespace Newwebsouth\Orm\QueryWriter\Mysql;


use Newwebsouth\Orm\Store;

abstract class AbstractAlterData
{
    
    protected array                  $toBind = array();
    
    
    /**
     * Bind value and
     *
     * @param \PDOStatement $statement
     * @param object $object
     */
    protected function bindValue( \PDOStatement $statement, object $object ): void
    {
        foreach( $this->toBind as $propertyName => $columnName ) {
            $reflectionProperty = Store::getReflection( get_class( $object ), $propertyName );
            
            if( $reflectionProperty->isInitialized( $object ) ) {
                
                $value = $reflectionProperty->getValue( $object );
                
                if( is_object( $value ) ) {
                    $reflectionPropertyOfValue = Store::getReflection( get_class( $object ), 'id' );
                    
                    if( $reflectionPropertyOfValue->isInitialized( $value ) && !empty( $id = $reflectionPropertyOfValue->getValue( $object ) ) ) {
                        
                        $statement->bindValue( ':' . $columnName, $id );
                    } else {
                        $statement->bindValue( ':' . $columnName, NULL );
                    }
                } else {
                    $statement->bindValue( ':' . $columnName, $reflectionProperty->getValue( $object ) );
                }
            }
        }
    }
}
