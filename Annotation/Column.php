<?php


namespace Nomess\Component\Orm\Annotation;


/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 * @Annotation
 */
class Column
{
    private string $name;
    private string $type;
    private string $length;
    private array $options;
    private string $index;
}
