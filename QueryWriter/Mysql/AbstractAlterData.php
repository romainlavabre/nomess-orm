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
                    $reflectionPropertyOfValue = Store::getReflection( get_class( $value ), 'id' );
                    
                    if( $reflectionPropertyOfValue->isInitialized( $value ) && !empty( $id = $reflectionPropertyOfValue->getValue( $value ) ) ) {
                        
                        $statement->bindValue( ':' . $columnName, $id );
                    } else {
                        $statement->bindValue( ':' . $columnName, NULL );
                    }
                } else {
                    if(is_bool($value)){
                        $value = (int)$value;
                    }elseif(is_array($value)){
                        $value = serialize($value);
                    }
                    
                    $statement->bindValue( ':' . $columnName, $value );
                }
            }else{
                if($reflectionProperty->getType()->getName() === 'array') {
                    $statement->bindValue( ':' . $columnName, serialize(array()) );
                }else {
                    $statement->bindValue( ':' . $columnName, NULL );
                }
                
            }
        }
        
        $this->toBind = array();
    }
}
