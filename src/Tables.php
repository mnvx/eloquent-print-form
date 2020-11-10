<?php

namespace Mnvx\EloquentPrintForm;

use Illuminate\Database\Eloquent\Model;

class Tables
{
    /**
     * @var Table[] List of tables where keys are prefixes
     */
    protected array $tables;

    public function __construct()
    {
        $this->tables = [];
    }

    /**
     * @param string $variable
     * @param Model[] $entities
     * @param string $prefix
     * @throws PrintFormException
     */
    public function add(string $variable, \Traversable $entities, string $prefix)
    {
        if (empty($this->tables[$prefix])) {
            $this->tables[$prefix] = new Table($entities, $prefix);
        }
        $this->tables[$prefix]->putVariable($variable);
    }

    /**
     * @return Table[]
     */
    public function get(): array
    {
        return $this->tables;
    }

}
