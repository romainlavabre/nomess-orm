<?php

namespace Nomess\Component\Orm\Analyze;

class LauncherAnalyze
{
    
    private Table    $table;
    private Column   $column;
    private Relation $relation;
    
    
    public function __construct(
        Table $table,
        Column $column,
        Relation $relation
    )
    {
        $this->table    = $table;
        $this->column   = $column;
        $this->relation = $relation;
    }
    
    
    public function launch(): void
    {
        echo "Launch the analyze of tables...\n";
        $this->table->revalideTables();
        echo "Launch the analyze of columns...\n";
        $this->column->revalideColumns();
        echo "Launch the analyze of relations...\n";
        $this->relation->revalideRelation();
        
        echo "Your database is updated\n";
    }
}
