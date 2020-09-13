<?php


namespace Nomess\Component\Orm\Cache\Builder;


use App\Entity\DataCustomer;
use Nomess\Component\Orm\Annotation\AnnotationParserInterface;
use Nomess\Component\Orm\Annotation\Parser;
use Nomess\Component\Orm\Exception\ORMException;

class RelationBuilder
{
    
    private const MANY_TO_MANY = 'ManyToMany';
    private const MANY_TO_ONE  = 'ManyToOne';
    private const ONE_TO_MANY  = 'OneToMany';
    private const ONE_TO_ONE   = 'OneToOne';
    private AnnotationParserInterface  $annotationParser;
    private TableBuilder               $tableBuilder;
    private \ReflectionProperty        $reflectionProperty;
    
    
    public function __construct( AnnotationParserInterface $annotationParser )
    {
        $this->annotationParser = $annotationParser;
        $this->tableBuilder     = new TableBuilder();
    }
    
    
    /**
     * Control that this property is relation or not
     *
     * @return bool
     * @throws ORMException
     */
    public function isRelation(): bool
    {
        $error = 0;
        
        try {
            $this->getType();
        } catch( ORMException $exception ) {
            $error++;
        }
        
        try {
            $this->getRelationClassname();
        } catch( ORMException $exception ) {
            $error++;
        }
        
        if( $error === 2 ) {
            return FALSE;
        }
        
        if( $error === 1 ) {
            $this->getType();
            $this->getRelationClassname();
        }
        
        return TRUE;
    }
    
    
    /**
     * Return the type of relation (Many, One ...)
     *
     * @return string
     * @throws ORMException
     */
    public function getType(): string
    {
        if( $this->annotationParser->has( self::MANY_TO_MANY, $this->reflectionProperty ) ) {
            return self::MANY_TO_MANY;
        }
        
        if( $this->annotationParser->has( self::MANY_TO_ONE, $this->reflectionProperty ) ) {
            return self::MANY_TO_ONE;
        }
        
        if( $this->annotationParser->has( self::ONE_TO_MANY, $this->reflectionProperty ) ) {
            return self::ONE_TO_MANY;
        }
        
        if( $this->annotationParser->has( self::ONE_TO_ONE, $this->reflectionProperty ) ) {
            return self::ONE_TO_ONE;
        }
        
        throw new ORMException( 'Impossible to resolving the type of relation for ' .
                                $this->reflectionProperty->getDeclaringClass()->getName() . '::$' . $this->reflectionProperty->getName() );
    }
    
    
    /**
     * Return classname of relation
     *
     * @return string
     * @throws ORMException
     */
    public function getRelationClassname(): string
    {
        $classname = $this->reflectionProperty->getType()->getName();
        
        if( $classname === 'array' ) {
            
            $shortClassName = $this->annotationParser->grossValue( 'var', $this->reflectionProperty );
            
            if($shortClassName === NULL){
                throw new ORMException('Impossible to resolving type of ' . $this->reflectionProperty->getName() .
                ' in ' . $this->reflectionProperty->getDeclaringClass()->getName() . '::class missing @var annotation');
            }
            
            $classname = $this->getRelationClassnameByVar(
                str_replace(['|', '[', ']', 'null', 'NULL'], '', $this->annotationParser->grossValue( 'var', $this->reflectionProperty )),
                $this->reflectionProperty->getDeclaringClass() );
        }
        
        if( !class_exists( $classname ) ) {
            throw new ORMException( 'Impossible to resolving the type of relation for ' .
                                    $this->reflectionProperty->getDeclaringClass()->getName() . '::$' . $this->reflectionProperty->getName() );
        }
        
        return $classname;
    }
    
    
    /**
     * Return the join table for ManyToMany relation
     *
     * @return string|null
     * @throws ORMException
     */
    public function getJoinTable(): ?string
    {
        if( $this->getType() !== 'ManyToMany' ) {
            return NULL;
        }
        
        $table1 = $this->tableBuilder->setReflectionClass( $this->reflectionProperty->getDeclaringClass() )->getTable();
        $table2 = $this->tableBuilder->setReflectionClass( new \ReflectionClass( $this->getRelationClassname() ) )->getTable();
        
        $alpha = [
            1  => 'a',
            2  => 'b',
            3  => 'c',
            4  => 'd',
            5  => 'e',
            6  => 'f',
            7  => 'g',
            8  => 'h',
            9  => 'i',
            10 => 'j',
            11 => 'k',
            12 => 'l',
            13 => 'm',
            14 => 'n',
            15 => 'o',
            16 => 'p',
            17 => 'q',
            18 => 'r',
            19 => 's',
            20 => 't',
            21 => 'u',
            22 => 'v',
            23 => 'w',
            24 => 'x',
            25 => 'y',
            26 => 'z'
        ];
        
        $iteration   = strlen( $table1 ) > strlen( $table2 ) ? strlen( $table1 ) : strlen( $table2 );
        $arrayTable1 = str_split( $table1 );
        $arrayTable2 = str_split( $table2 );
        $scoreTable1 = 0;
        $scoreTable2 = 0;
        
        for( $i = 0; $i < $iteration; $i++ ) {
            if( array_key_exists( $i, $arrayTable1 ) && array_key_exists( $i, $arrayTable2 ) ) {
                if( in_array( $arrayTable1[$i], $alpha ) ) {
                    $scoreTable1 += array_search( $arrayTable1[$i], $alpha );
                }
                
                if( in_array( $arrayTable2[$i], $alpha ) ) {
                    $scoreTable2 += array_search( $arrayTable2[$i], $alpha );
                }
            } else {
                
                if( $scoreTable1 > $scoreTable2 ) {
                    return $table2 . '_' . $table1;
                } elseif( $scoreTable2 > $scoreTable1 ) {
                    return $table1 . '_' . $table2;
                }
                
                if( !array_key_exists( $i, $arrayTable1 ) ) {
                    return $table1 . '_' . $table2;
                } else {
                    return $table2 . '_' . $table1;
                }
            }
        }
        
        if( $scoreTable1 > $scoreTable2 ) {
            return $table2 . '_' . $table1;
        }
        
        return $table1 . '_' . $table2;
    }
    
    
    /**
     * Return the inversed property
     *
     * @param string $classname
     * @param string $relationType
     * @return string|null
     * @throws ORMException
     * @throws \ReflectionException
     */
    public function getInversed(string $classname, string $relationType): ?string
    {
        foreach((new \ReflectionClass( $classname))->getProperties() as $reflectionProperty){
            $relationBuilder = new RelationBuilder( new Parser());
            $relationBuilder->setReflectionProperty( $reflectionProperty);
    
            if($relationBuilder->isRelation()
               && ($relationBuilder->getRelationClassname() === $this->reflectionProperty->getDeclaringClass()->getName())){
    
                $tmp = explode( 'To', $relationType);
                $relationTypeInversed =  $tmp[1] . 'To' . $tmp[0];
    
                if($relationBuilder->getType() === $relationTypeInversed){
                    return $reflectionProperty->getName();
                }
            }
        }
        
        return NULL;
    }
    
    
    /**
     * If property is of type array, search the classname
     *
     * @param string $classname
     * @param \ReflectionClass $reflectionClass
     * @return string|null
     * @throws ORMException
     */
    private function getRelationClassnameByVar( string $classname, \ReflectionClass $reflectionClass ): ?string
    {
        //Search for class in used namespace
        $file  = file( $reflectionClass->getFileName() );
        $found = array();
        
        
        foreach( $file as $line ) {
            
            if( strpos( $line, $classname ) !== FALSE && strpos( $line, 'use' ) !== FALSE ) {
                
                preg_match( '/ +[A-Za-z0-9_\\\]*/', $line, $output );
                $found[] = trim( $output[0] );
            }
        }
        if( empty( $found ) ) {
            if( class_exists( $reflectionClass->getNamespaceName() . '\\' . $classname ) ) {
                return $reflectionClass->getNamespaceName() . '\\' . $classname;
            }
        } elseif( count( $found ) === 1 ) {
            return $found[0];
        }
        
        throw new ORMException( 'ORM encountered an error: impossible to resolving the type ' . $classname . ' in @var annotation in ' . $reflectionClass->getName() );
    }
    
    
    public function setReflectionProperty( \ReflectionProperty $reflectionProperty ): void
    {
        $this->reflectionProperty = $reflectionProperty;
    }
}
