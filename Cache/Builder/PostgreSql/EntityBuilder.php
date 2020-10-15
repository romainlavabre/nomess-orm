<?php


namespace Nomess\Component\Orm\Cache\Builder\PostgreSql;


use App\Entity\Admin;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Orm\Exception\ORMException;
use Nomess\Component\Parser\AnnotationParserInterface;
use Nomess\Component\Security\User\SecurityUser;
use ReflectionProperty;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class EntityBuilder
{
    
    private const COLUMN           = 'Column';
    private const NAME             = 'name';
    private const TYPE             = 'type';
    private const LENGTH           = 'length';
    private const OPTIONS          = 'options';
    private const INDEX            = 'index';
    private const AVAILABLE_TYPE   = [
        'string' => 'CHARACTER VARYING',
        'array'  => 'TEXT',
        'int'    => 'INTEGER',
        'float'  => 'DOUBLE PRECISION',
        'double' => 'DOUBLE PRECISION',
        'bool'   => 'BOOLEAN'
    ];
    private const AVAILABLE_LENGTH = [
        'CHARACTER'         => 1,
        'CHARACTER VARYING' => 255,
        'TEXT'              => NULL,
        'TSQUERY'           => NULL,
        'TSVECTOR'          => NULL,
        'UUID'              => NULL,
        'XML'               => NULL,
        'JSON'              => NULL,
        'JSONB'             => NULL,
        'SMALLINT'          => NULL,
        'INTEGER'           => NULL,
        'BIGINT'            => NULL,
        'REAL'            => NULL,
        'NUMERIC'           => NULL,
        'DOUBLE PRECISION'  => NULL,
        'MONEY'  => NULL,
        'BOOLEAN' => NULL,
        'DATE'              => NULL,
        'DATETIME'          => NULL,
        'TIMESTAMP'         => NULL,
        'TIMESTAMPTZ'       => NULL,
        'INTERVAL'          => NULL,
        'BIT'               => 1,
        'BINARY'            => 5,
        'VARBINARY'         => 8,
        'TINYBLOB'          => NULL,
        'BLOB'              => NULL,
        'MEDIUMBLOB'        => NULL,
        'LONGBLOB'          => NULL
    
    ];
    private const AVAILABLE_INDEX  = [
        'PRIMARY',
        'UNIQUE',
        'INDEX'
    ];
    private AnnotationParserInterface $annotationParser;
    private RelationBuilder           $relationBuilder;
    private ReflectionProperty        $reflectionProperty;
    
    
    public function __construct(
        AnnotationParserInterface $annotationParser,
        RelationBuilder $relationBuilder )
    {
        $this->annotationParser = $annotationParser;
        $this->relationBuilder  = $relationBuilder;
    }
    
    
    public function getColumn(): string
    {
        if( $this->annotationParser->has( self::COLUMN, $this->reflectionProperty ) ) {
            if( array_key_exists( self::NAME, $value = $this->annotationParser->getValue( self::COLUMN, $this->reflectionProperty ) ) ) {
                return $value[self::NAME];
            }
        }
        
        return $this->reflectionProperty->getName();
    }
    
    
    /**
     * @return string|null
     * @throws ORMException
     */
    public function getColumnLength(): ?string
    {
        $columnType = $this->getColumnType();
        
        if( class_exists( $columnType ) ) {
            return $columnType;
        }
        
        if( $this->annotationParser->has( self::COLUMN, $this->reflectionProperty ) ) {
            if( array_key_exists( self::LENGTH, $value = $this->annotationParser->getValue( self::COLUMN, $this->reflectionProperty ) ) ) {
                return $value[self::LENGTH];
            }
        }
        
        if( !array_key_exists( $columnType, self::AVAILABLE_LENGTH ) ) {
            throw new ORMException( 'Please, specify the length of property "' . $this->reflectionProperty->getName() . '" in class "' .
                                    $this->reflectionProperty->getDeclaringClass()->getName() . '"' );
        }
        
        return self::AVAILABLE_LENGTH[$columnType];
    }
    
    
    public function getColumnType(): string
    {
        if( $this->annotationParser->has( self::COLUMN, $this->reflectionProperty ) ) {
            if( array_key_exists( self::TYPE, $value = $this->annotationParser->getValue( self::COLUMN, $this->reflectionProperty ) ) ) {
                return mb_strtoupper( $value[self::TYPE] );
            }
        }
        
        $type = $this->reflectionProperty->getType()->getName();
        
        if( class_exists( $type ) ) {
            return $type;
        }
        
        if( !array_key_exists( $type, self::AVAILABLE_TYPE ) ) {
            
            throw new ORMException( 'The type "' . $type . '" is not supported by nomess/orm, please, specify explicitly type or annot by "@Stateless"' );
        }
        
        return self::AVAILABLE_TYPE[$type];
    }
    
    
    public function getColumnOptions(): ?string
    {
        $options = NULL;
        
        if( $this->annotationParser->has( self::COLUMN, $this->reflectionProperty ) ) {
            $value = $this->annotationParser->getValue( self::COLUMN, $this->reflectionProperty );
            
            if( array_key_exists( self::OPTIONS, $value ) ) {
                $options = '';
                
                if( !is_array( $value[self::OPTIONS] ) ) {
                    throw new ORMException( 'The argument "options" must be a array for "' . $this->reflectionProperty->getName() .
                                            '" in ' . $this->reflectionProperty->getDeclaringClass()->getName() . '::class"' );
                }
                
                foreach( $value[self::OPTIONS] as $option ) {
                    $options .= $option . ' ';
                }
            } else {
                $options = '';
            }
        }
        
        return !empty( $options ) ? $options : NULL;
    }
    
    
    public function getColumnIndex(): ?string
    {
        if( !$this->annotationParser->has( self::COLUMN, $this->reflectionProperty ) ) {
            return NULL;
        }
        
        $content = $this->annotationParser->getValue( self::COLUMN, $this->reflectionProperty );
        
        if( !array_key_exists( self::INDEX, $content ) ) {
            return NULL;
        }
        
        if( !in_array( mb_strtoupper( $content[self::INDEX] ), self::AVAILABLE_INDEX, TRUE ) ) {
            throw new ORMException( 'The index type "' . $content[self::INDEX] . '" is not supported' );
        }
        
        return mb_strtoupper( $content[self::INDEX] );
    }
    
    
    public function getType(): string
    {
        return $this->reflectionProperty->getType()->getName();
    }
    
    
    public function getRelation(): ?array
    {
        if( !$this->relationBuilder->isRelation() ) {
            return NULL;
        }
        
        return [
            CacheHandlerInterface::ENTITY_RELATION_TYPE       => $relationType = $this->relationBuilder->getType(),
            CacheHandlerInterface::ENTITY_RELATION_CLASSNAME  => $relationClassname = $this->relationBuilder->getRelationClassname(),
            CacheHandlerInterface::ENTITY_RELATION_JOIN_TABLE => $this->relationBuilder->getJoinTable(),
            CacheHandlerInterface::ENTITY_RELATION_INVERSED   => $inversed = $this->relationBuilder->getInversed( $relationClassname, $relationType ),
            CacheHandlerInterface::ENTITY_RELATION_OWNER      => $this->relationBuilder->isOwner( !empty( $inversed ) ? new ReflectionProperty( $relationClassname, $inversed ) : NULL, $relationClassname ),
            CacheHandlerInterface::ENTITY_RELATION_ON_UPDATE  => $this->relationBuilder->onUpdate(),
            CacheHandlerInterface::ENTITY_RELATION_ON_DELETE  => $this->relationBuilder->onDelete()
        ];
    }
    
    
    public function isValidProperty(): bool
    {
        if( $this->annotationParser->has( 'StateLess', $this->reflectionProperty ) ) {
            return FALSE;
        }
        
        if( !$this->reflectionProperty->hasType() ) {
            throw new ORMException( 'Please, propose type for ' .
                                    $this->reflectionProperty->getDeclaringClass()->getName() .
                                    '::$' . $this->reflectionProperty->getName() . ' or add annotation "@StateLess"' );
        }
        
        return TRUE;
    }
    
    
    public function isNullable(): bool
    {
        return $this->reflectionProperty->getType()->allowsNull();
    }
    
    
    public function setReflectionProperty( ReflectionProperty $reflectionProperty ): void
    {
        $this->reflectionProperty = $reflectionProperty;
        $this->relationBuilder->setReflectionProperty( $reflectionProperty );
    }
}
