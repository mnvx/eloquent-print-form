<?php

namespace Mnvx\EloquentPrintForm;

use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpWord\TemplateProcessor;

class PrintFormProcessor
{
    protected string $tmpPrefix;

    protected Pipes $pipes;

    public function __construct(Pipes $pipes = null, string $tmpPrefix = 'pfp_')
    {
        $this->tmpPrefix = $tmpPrefix;
        if ($pipes === null) {
            $this->pipes = new Pipes();
        }
    }

    /**
     * @param string $templateFile
     * @param Model $entity
     * @param callable[] $customSetters Keys are variables, values are callbacks
     * with signature callback(TemplateProcessor $processor, string $variable, ?string $value)
     * @return string Temporary file name with processed document
     * @throws PrintFormException
     */
    public function process(string $templateFile, Model $entity, array $customSetters = []): string
    {
        try {
            $templateProcessor = new TemplateProcessor($templateFile);
        } catch (\Exception $e) {
            throw new PrintFormException("Cant create processor for '$templateFile'. " . $e->getMessage());
        }
        $tables = new Tables();

        // Process simple fields and collect information about table fields
        foreach ($templateProcessor->getVariables() as $variable) {
            [$current, $isTable] = $this->getValue($entity, $variable, $tables);
            if ($isTable) {
                continue;
            }
            if (isset($customSetters[$variable])) {
                $customSetters[$variable]($templateProcessor, $variable, $current);
            }
            else {
                $templateProcessor->setValue($variable, $current);
            }
        }

        // Process table fields
        foreach ($tables->get() as $table) {
            $this->processTable($table, $templateProcessor);
        }

        $tempFileName = tempnam(sys_get_temp_dir(), $this->tmpPrefix);
        $templateProcessor->saveAs($tempFileName);
        return $tempFileName;
    }

    /**
     * @param Table $table
     * @param TemplateProcessor $templateProcessor
     * @throws PrintFormException
     */
    protected function processTable(Table $table, TemplateProcessor $templateProcessor)
    {
        $marker = $table->marker();
        $values = [];
        $rowNumber = 0;
        foreach ($table->entities() as $entity) {
            $rowNumber++;
            $item = [];
            foreach ($table->variables() as $variable => $shortVariable) {
                $marker = $variable;
                [$item[$variable], $isTable] = $this->getValue($entity, $shortVariable);
            }
            $item[$table->marker() . '#row_number'] = $rowNumber;
            $values[] = $item;
        }
        if (empty($values)) {
            $item = [];
            foreach ($table->variables() as $variable => $shortVariable) {
                $marker = $variable;
                $item[$variable] = $this->pipes->placeholder;
            }
            $item[$table->marker() . '#row_number'] = '_';
            $values[] = $item;
        }
        $templateProcessor->cloneRowAndSetValues($marker, $values);
    }

    /**
     * @param Model $entity
     * @param string $variable
     * @param Tables|null $tables
     * @return array [value, is table]
     * @throws PrintFormException
     */
    protected function getValue(Model $entity, string $variable, Tables $tables = null): array
    {
        $pipes = explode('|', $variable);
        $parts = explode('.', array_shift($pipes));
        $current = $entity;
        $prefix = '';
        foreach ($parts as $part) {
            $prefix .= $part . '.';
            if (!is_object($current) && ! $current instanceof \Traversable) {
                $current = '';
                break;
            }
            try {
                $current = $current->$part;
            } catch (\Throwable $e) {
                $current = '';
                break;
            }
            if ($current instanceof \Traversable) {
                if ($tables) {
                    $tables->add($variable, $current, $prefix);
                }
                return [null, true];
            }
        }

        // Process pipes
        foreach ($pipes as $pipe) {
            try {
                $current = $this->pipes->$pipe($current);
            } catch (\Throwable $e) {
                throw new PrintFormException("Cant process pipe '$pipe' for expression `$variable`. " .
                    $e->getMessage());
            }
        }
        return [$current, false];
   }
}
