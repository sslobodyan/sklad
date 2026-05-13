<?php
/**
 * Trait ResourceFormatTrait
 * Форматування значень ресурсів
 */
trait ResourceFormatTrait
{
    /**
     * Форматування дельти для примітки
     */
    protected function formatDelta(float $delta, string $fmt): string
    {
        if ($fmt === 'hm') {
            $h = (int)floor($delta);
            $m = (int)round(($delta - $h) * 60);
            return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
        } elseif ($fmt === 'int') {
            return (string)(int)$delta;
        }
        return number_format($delta, 2, '.', '');
    }

    /**
     * Форматування значення для експорту
     */
    public function formatValue($value, string $format = 'dec2'): string
    {
        if ($value === null || $value === '') return '';
        $v = (float)$value;
        switch ($format) {
            case 'int': return (string)(int)$v;
            case 'hm':
                $h = (int)floor($v);
                $m = (int)round(($v - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            default: return number_format($v, 2, '.', '');
        }
    }
}