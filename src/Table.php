<?php

namespace Mnvx\EloquentPrintForm;

use Illuminate\Database\Eloquent\Model;

class Table
{
    /**
     * @var string e.g. 'placements.', 'project.placements.'
     */
    protected string $tablePrefix;

    /**
     * @var Model[] Entities of table
     */
    protected $entities;

    /**
     * @var string[] All variables of current table.
     * Keys are variables, values are variables without prefixes
     * e.g. [
     *     'placements.issue.edition' => 'issue.edition',
     *     'placements.issue.date' => 'issue.date',
     * ]
     */
    protected array $variables;

    /**
     * Table constructor.
     * @param Model[] $entities
     * @param string $tablePrefix
     */
    public function __construct(\Traversable $entities, string $tablePrefix)
    {
        $this->tablePrefix = $tablePrefix;
        $this->entities = $entities;
        $this->variables = [];
    }

    /**
     * @param string $variable
     * @throws PrintFormException
     */
    public function putVariable(string $variable)
    {
        if (mb_strpos($variable, $this->tablePrefix) !== 0) {
            throw new PrintFormException("Cant put variable. Prefixes not matched. " .
                "Prefix '$this->tablePrefix'. Variable '$variable'.");
        }
        $this->variables[$variable] = mb_substr($variable, mb_strlen($this->tablePrefix));
    }

    public function marker(): string
    {
        return $this->tablePrefix;
    }

    /**
     * @return Model[]
     */
    public function entities(): \Traversable
    {
        return $this->entities;
    }

    /**
     * @return string[] Keys are variables, values are variables without prefixes
     */
    public function variables(): array
    {
        return $this->variables;
    }

}
