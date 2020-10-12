<?php
/** @noinspection PhpUndefinedConstantInspection */


namespace Nomess\Component\Orm\Cache\Builder\Mysql;


use Nomess\Component\Orm\Cache\Builder\CacheBuilderInterface;
use Nomess\Component\Orm\Cache\CacheHandlerInterface;
use Nomess\Component\Parser\AnnotationParserInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class CacheBuilder implements CacheBuilderInterface
{
    
    private EntityBuilder             $entityBuilder;
    private TableBuilder              $tableBuilder;
    private AnnotationParserInterface $annotationParser;
    private array                     $cache = array();
    
    
    /**
     * *
     * @param EntityBuilder $entityBuilder
     * @param TableBuilder $tableBuilder
     * @param AnnotationParserInterface $annotationParser
     */
    public function __construct(
        EntityBuilder $entityBuilder,
        TableBuilder $tableBuilder,
        AnnotationParserInterface $annotationParser )
    {
        $this->entityBuilder    = $entityBuilder;
        $this->tableBuilder     = $tableBuilder;
        $this->annotationParser = $annotationParser;
    }
    
    
    public function buildCache( string $classname ): array
    {
        $reflectionProperties = ( $reflectionClass = new \ReflectionClass( $classname ) )->getProperties();
        
        $this->cache[CacheHandlerInterface::TABLE_METADATA] = [
            CacheHandlerInterface::TABLE_NAME => $this->tableBuilder->setReflectionClass( $reflectionClass )->getTable()
        ];
        
        foreach( $reflectionProperties as $reflectionProperty ) {
            if( $this->annotationParser->has( 'Stateless', $reflectionProperty ) ) {
                continue;
            }
            
            $this->entityBuilder->setReflectionProperty( $reflectionProperty );
            
            if( $this->entityBuilder->isValidProperty() ) {
                
                $this->cache[CacheHandlerInterface::ENTITY_METADATA][$reflectionProperty->getName()] = [
                    CacheHandlerInterface::ENTITY_COLUMN_NAME    => $this->entityBuilder->getColumn(),
                    CacheHandlerInterface::ENTITY_COLUMN_TYPE    => $this->entityBuilder->getColumnType(),
                    CacheHandlerInterface::ENTITY_COLUMN_LENGTH  => $this->entityBuilder->getColumnLength(),
                    CacheHandlerInterface::ENTITY_COLUMN_OPTIONS => $this->entityBuilder->getColumnOptions(),
                    CacheHandlerInterface::ENTITY_COLUMN_INDEX   => $this->entityBuilder->getColumnIndex(),
                    CacheHandlerInterface::ENTITY_TYPE           => $this->entityBuilder->getType(),
                    CacheHandlerInterface::ENTITY_RELATION       => $this->entityBuilder->getRelation(),
                    CacheHandlerInterface::ENTITY_IS_NULLABLE    => $this->entityBuilder->isNullable()
                ];
            }
        }
        
        $cache       = $this->cache;
        $this->cache = array();
        
        return $cache;
    }
}
