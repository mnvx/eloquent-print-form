<?php

namespace Mnvx\EloquentPrintForm;

use Carbon\Carbon;

class Pipes
{
    public string $dateFormat;
    public string $datetimeFormat;
    public string $placeholder;
    public int $decimals;
    public string $decPoint;
    public string $thousandsSep;

    public function __construct(
        string $placeholder = '____________________',
        string $dateFormat = 'd.m.Y',
        string $datetimeFormat = 'd.m.Y H:i:s',
        int $decimals = 2,
        string $decPoint = '.',
        string $thousandsSep = '`'
    )
    {
        $this->placeholder = $placeholder;
        $this->dateFormat = $dateFormat;
        $this->datetimeFormat = $datetimeFormat;
        $this->decimals = $decimals;
        $this->decPoint = $decPoint;
        $this->thousandsSep = $thousandsSep;
    }

    public function placeholder($value)
    {
        return $value ?: $this->placeholder;
    }

    public function date($value)
    {
        if ($value === null) {
            return null;
        }
        return Carbon::parse($value)->format($this->dateFormat);
    }

    public function dateTime($value)
    {
        if ($value === null) {
            return null;
        }
        return Carbon::parse($value)->format($this->datetimeFormat);
    }

    public function int($value)
    {
        return number_format($value, 0, $this->decPoint, $this->thousandsSep);
    }

    public function decimal($value)
    {
        return number_format($value, $this->decimals, $this->decPoint, $this->thousandsSep);
    }

}
