<?php
/**
 * ArrayDataProcessor
 *
 * A robust, production-grade PHP class for advanced array data manipulation, filtering, sorting, grouping, and transformation.
 *
 * Features:
 * - Field casting and aliasing
 * - Filtering (with AND/OR logic)
 * - Multi-level sorting
 * - Pagination (limit/offset)
 * - Grouping by any key (supports dot notation)
 * - Array utility methods: count, first, last, random, reverse, shuffle, pluck, filter, map, sum, avg, min, max
 * - CSV/JSON export
 * - Logging of all operations
 *
 * @author  Your Name
 * @license MIT
 * @version 1.0.0
 */
class ArrayDataProcessor
{
    // Data and configuration
    protected $data = [];
    protected $filters = [];
    protected $filterLogic = 'AND';
    protected $aliases = [];
    protected $casts = [];
    protected $sortFields = [];
    protected $limit = -1;
    protected $offset = 0;
    protected $fields = [];
    protected $log = [];
    protected $enumMap = [];

    /**
     * Constructor
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Set the type cast for a field
     * @param string $field
     * @param string|callable $type
     * @return $this
     */
    public function setFieldType($field, $type)
    {
        $this->casts[$field] = $type;
        return $this;
    }

    /**
     * Set an alias for a field
     * @param string $field
     * @param string $alias
     * @return $this
     */
    public function setFieldAlias($field, $alias)
    {
        $this->aliases[$field] = $alias;
        return $this;
    }

    /**
     * Set the output fields (supports dot notation for nested fields)
     * @param array $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Add additional fields to output
     * @param array $keys
     * @return $this
     */
    public function selectKeys($keys)
    {
        $this->fields = array_merge($this->fields, $keys);
        return $this;
    }

    /**
     * Set multiple filters at once
     * @param array $filterDefinitions
     * @return $this
     */
    public function setFilters($filterDefinitions)
    {
        foreach ($filterDefinitions as $field => $rule) {
            $this->addFilter(function ($row) use ($field, $rule) {
                $value = isset($row[$field]) ? $row[$field] : null;
                $expected = $rule['value'];
                switch ($rule['type']) {
                    case 'equals': return $value == $expected;
                    case 'notEquals': return $value != $expected;
                    case 'greaterThan': return $value > $expected;
                    case 'greaterThanOrEqual': return $value >= $expected;
                    case 'lessThan': return $value < $expected;
                    case 'lessThanOrEqual': return $value <= $expected;
                    case 'in': return in_array($value, (array) $expected);
                    case 'notIn': return !in_array($value, (array) $expected);
                    case 'like': return strpos((string) $value, (string) $expected) !== false;
                    case 'startsWith': return strpos((string) $value, (string) $expected) === 0;
                    case 'endsWith': return substr((string) $value, -strlen((string) $expected)) === (string) $expected;
                    case 'between': return $value >= $expected[0] && $value <= $expected[1];
                    case 'null': return is_null($value);
                    case 'notNull': return !is_null($value);
                    case 'empty': return empty($value);
                    case 'notEmpty': return !empty($value);
                    default: return true;
                }
            }, $field);
        }
        return $this;
    }

    /**
     * Add a filter callback
     * @param callable $callback
     * @param string|null $name
     * @return $this
     */
    public function addFilter($callback, $name = null)
    {
        if ($name) {
            $this->filters[$name] = $callback;
        } else {
            $this->filters[] = $callback;
        }
        return $this;
    }

    /**
     * Remove a filter by name
     * @param string $name
     * @return $this
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);
        return $this;
    }

    /**
     * Set filter logic (AND/OR)
     * @param string $logic
     * @return $this
     */
    public function setFilterLogic($logic)
    {
        $this->filterLogic = strtoupper($logic);
        return $this;
    }

    /**
     * Set all field aliases at once
     * @param array $aliases
     * @return $this
     */
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Set all field casts at once
     * @param array $casts
     * @return $this
     */
    public function setCasts($casts)
    {
        $this->casts = $casts;
        return $this;
    }

    /**
     * Set enum mapping for fields
     * @param array $map
     * @return $this
     */
    public function setEnumMap($map)
    {
        $this->enumMap = $map;
        return $this;
    }

    /**
     * Add a sort field (multi-level sorting supported)
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function addSortBy($field, $direction = 'asc')
    {
        $this->sortFields[] = ['field' => $field, 'direction' => strtolower($direction)];
        return $this;
    }

    /**
     * Set result limit
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set result offset
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Apply all filters to data
     * @param array $data
     * @return array
     */
    protected function applyFilters($data)
    {
        if (empty($this->filters)) return $data;
        if (isset($this->filters['__group__'])) {
            $filtered = array_filter($data, function($row) {
                return call_user_func($this->filters['__group__'], $row);
            });
            $this->log[] = "Applied grouped filter";
            return $filtered;
        }
        $initialCount = count($data);
        $filtered = array_filter($data, function ($row) {
            $results = array();
            foreach ($this->filters as $filter) {
                $results[] = $filter($row);
            }
            return $this->filterLogic === 'AND'
                ? !in_array(false, $results, true)
                : in_array(true, $results, true);
        });
        $filteredCount = count($filtered);
        $this->log[] = "Filtered records count: $filteredCount out of $initialCount";
        return $filtered;
    }

    /**
     * Apply multi-level sorting
     * @param array $data
     * @return array
     */
    protected function applySorting($data)
    {
        if (empty($this->sortFields)) return $data;
        usort($data, array($this, '_multiSortCompare'));
        return $data;
    }

    /**
     * Multi-level sort comparison
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _multiSortCompare($a, $b)
    {
        foreach ($this->sortFields as $sort) {
            $field = $sort['field'];
            $direction = isset($sort['direction']) ? strtolower($sort['direction']) : 'asc';
            $aVal = isset($a[$field]) ? $a[$field] : null;
            $bVal = isset($b[$field]) ? $b[$field] : null;
            if ($aVal == $bVal) continue;
            $cmp = ($aVal < $bVal) ? -1 : 1;
            return ($direction === 'asc') ? $cmp : -$cmp;
        }
        return 0;
    }

    /**
     * Apply pagination (limit/offset)
     * @param array $data
     * @return array
     */
    protected function applyPagination($data)
    {
        return $this->limit < 0 ? $data : array_slice($data, $this->offset, $this->limit);
    }

    /**
     * Cast and alias fields, flatten nested arrays
     * @param array $data
     * @return array
     */
    protected function castAndAlias($data)
    {
        $out = array();
        foreach ($data as $row) {
            $flattened = $this->flattenAndProcessRow($row);
            if ($flattened !== null) {
                $out[] = $flattened;
            }
        }
        return $out;
    }

    /**
     * Flatten and process a row, supporting dot notation and aliases
     * @param array $row
     * @param string $prefix
     * @return array
     */
    protected function flattenAndProcessRow($row, $prefix = '')
    {
        $result = array();
        foreach ($row as $key => $value) {
            $fullKey = $prefix . $key;
            if (is_array($value) && $this->isAssoc($value)) {
                $nested = $this->flattenAndProcessRow($value, $fullKey . '.');
                $result = array_merge($result, $nested);
            } else {
                $alias = isset($this->aliases[$fullKey]) ? $this->aliases[$fullKey] : $fullKey;
                $cast = isset($this->casts[$fullKey]) ? $this->casts[$fullKey] : null;
                $result[$alias] = $this->castValue($value, $cast, $fullKey);
            }
        }
        // If fields are set, ensure all requested fields are present (even if null)
        if (!empty($this->fields)) {
            $out = array();
            foreach ($this->fields as $field) {
                // Support dot notation for nested fields
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field);
                    $val = $row;
                    foreach ($parts as $part) {
                        if (is_array($val) && isset($val[$part])) {
                            $val = $val[$part];
                        } else {
                            $val = null;
                            break;
                        }
                    }
                    $out[$field] = $val;
                } else {
                    $out[$field] = isset($result[$field]) ? $result[$field] : null;
                }
            }
            return $out;
        }
        return $result;
    }

    /**
     * Check if array is associative
     * @param array $array
     * @return bool
     */
    protected function isAssoc($array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Cast a value to a given type or via callback
     * @param mixed $value
     * @param string|callable|null $type
     * @param string $key
     * @return mixed
     */
    protected function castValue($value, $type, $key = '')
    {
        if (!$type) return $value;
        if (is_callable($type)) return $type($value);
        switch ($type) {
            case 'int': return (int)$value;
            case 'float': return (float)$value;
            case 'string': return (string)$value;
            case 'bool': return (bool)$value;
            case 'json': return json_decode($value, true);
            case 'date': return date('Y-m-d', strtotime($value));
            case 'datetime':
            case 'timestamp': return date('Y-m-d H:i:s', strtotime($value));
            case 'enum': return isset($this->enumMap[$key][$value]) ? $this->enumMap[$key][$value] : $value;
            default: return $value;
        }
    }

    /**
     * Process the data (filters, sorting, pagination, casting, aliasing)
     * @return array
     */
    public function process()
    {
        $this->log[] = 'Start processing';
        $data = $this->applyFilters($this->data);
        $this->log[] = 'Applied filters';
        $data = $this->applySorting($data);
        $this->log[] = 'Applied sorting';
        $data = $this->applyPagination($data);
        $this->log[] = 'Applied pagination';
        $data = $this->castAndAlias($data);
        $this->log[] = 'Applied casting and aliasing';
        return $data;
    }

    /**
     * Get processed data as array
     * @return array
     */
    public function toArray()
    {
        $data = $this->process();
        $this->log[] = 'Output as array';
        return $data;
    }

    /**
     * Get processed data as JSON
     * @param int $flags
     * @return string
     */
    public function toJson($flags = 128)
    {
        $data = $this->process();
        $this->log[] = 'Output as JSON';
        return json_encode($data, $flags);
    }

    /**
     * Get processed data as CSV
     * @param string $delimiter
     * @param string $enclosure
     * @return string
     */
    public function toCsv($delimiter = ',', $enclosure = '"')
    {
        $data = $this->process();
        if (empty($data)) return '';
        $output = array();
        $headers = array_keys($data[0]);
        $output[] = implode($delimiter, $headers);
        foreach ($data as $row) {
            $output[] = implode($delimiter, array_map(function($v) use ($enclosure) {
                return $enclosure . str_replace($enclosure, $enclosure . $enclosure, $v) . $enclosure;
            }, $row));
        }
        $this->log[] = 'Output as CSV';
        return implode("\n", $output);
    }

    /**
     * Get CSV headers
     * @return array
     */
    public function getCsvHeaders()
    {
        $data = $this->process();
        if (empty($data)) return array();
        return array_keys($data[0]);
    }

    /**
     * Get log of operations
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Reset all configuration (except data)
     * @return $this
     */
    public function reset()
    {
        $this->filters = array();
        $this->filterLogic = 'AND';
        $this->aliases = array();
        $this->casts = array();
        $this->sortFields = array();
        $this->limit = -1;
        $this->offset = 0;
        $this->fields = array();
        $this->enumMap = array();
        $this->log = array();
        return $this;
    }

    /**
     * Group processed data by a key (supports dot notation)
     * @param string $key
     * @return array
     */
    public function groupBy($key)
    {
        $data = $this->process();
        $grouped = array();
        foreach ($data as $row) {
            $groupValue = null;
            if (strpos($key, '.') !== false) {
                $parts = explode('.', $key);
                $val = $row;
                foreach ($parts as $part) {
                    if (is_array($val) && isset($val[$part])) {
                        $val = $val[$part];
                    } else {
                        $val = null;
                        break;
                    }
                }
                $groupValue = $val;
            } else {
                $groupValue = isset($row[$key]) ? $row[$key] : null;
            }
            if (!isset($grouped[$groupValue])) {
                $grouped[$groupValue] = array();
            }
            $grouped[$groupValue][] = $row;
        }
        $this->log[] = "Grouped by " . $key;
        return $grouped;
    }

    // --- Array utility methods ---

    /**
     * Get count of processed records
     * @return int
     */
    public function count()
    {
        return count($this->process());
    }

    /**
     * Get first record
     * @return array|null
     */
    public function first()
    {
        $data = $this->process();
        return count($data) ? $data[0] : null;
    }

    /**
     * Get last record
     * @return array|null
     */
    public function last()
    {
        $data = $this->process();
        return count($data) ? $data[count($data)-1] : null;
    }

    /**
     * Get one or more random records
     * @param int $num
     * @return array|mixed|null
     */
    public function random($num = 1)
    {
        $data = $this->process();
        if (empty($data)) return null;
        if ($num === 1) {
            return $data[array_rand($data)];
        }
        $keys = array_rand($data, min($num, count($data)));
        if (!is_array($keys)) $keys = [$keys];
        $result = [];
        foreach ($keys as $k) {
            $result[] = $data[$k];
        }
        return $result;
    }

    /**
     * Get reversed processed data
     * @return array
     */
    public function reverse()
    {
        return array_reverse($this->process());
    }

    /**
     * Get shuffled processed data
     * @return array
     */
    public function shuffle()
    {
        $data = $this->process();
        shuffle($data);
        return $data;
    }

    /**
     * Pluck a single column from processed data
     * @param string $key
     * @return array
     */
    public function pluck($key)
    {
        $data = $this->process();
        $result = array();
        foreach ($data as $row) {
            $result[] = isset($row[$key]) ? $row[$key] : null;
        }
        return $result;
    }

    /**
     * Filter processed data with a callback
     * @param callable $callback
     * @return array
     */
    public function filter(callable $callback)
    {
        $data = $this->process();
        return array_values(array_filter($data, $callback));
    }

    /**
     * Map processed data with a callback
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback)
    {
        $data = $this->process();
        return array_map($callback, $data);
    }

    /**
     * Sum a column in processed data
     * @param string $key
     * @return float|int
     */
    public function sum($key)
    {
        $data = $this->process();
        $sum = 0;
        foreach ($data as $row) {
            $sum += isset($row[$key]) ? $row[$key] : 0;
        }
        return $sum;
    }

    /**
     * Average of a column in processed data
     * @param string $key
     * @return float|int
     */
    public function avg($key)
    {
        $data = $this->process();
        $count = count($data);
        return $count ? $this->sum($key) / $count : 0;
    }

    /**
     * Minimum value of a column in processed data
     * @param string $key
     * @return mixed
     */
    public function min($key)
    {
        $data = $this->process();
        $values = array();
        foreach ($data as $row) {
            if (isset($row[$key])) $values[] = $row[$key];
        }
        return empty($values) ? null : min($values);
    }

    /**
     * Maximum value of a column in processed data
     * @param string $key
     * @return mixed
     */
    public function max($key)
    {
        $data = $this->process();
        $values = array();
        foreach ($data as $row) {
            if (isset($row[$key])) $values[] = $row[$key];
        }
        return empty($values) ? null : max($values);
    }
}

// --- Example usage ---

// Example data array
$data = [
    [
        'id' => '1',
        'title' => 'Pro Gaming',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '1200',
        'created_at' => '2025-05-16 08:00:00',
        'details' => json_encode([
            'platform' => 'Twitch',
            'quality' => 'HD'
        ])
    ],
    [
        'id' => '2',
        'title' => 'Chill Stream',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '800',
        'created_at' => '2025-05-10 10:30:00',
        'details' => json_encode([
            'platform' => 'YouTube',
            'quality' => 'SD'
        ])
    ],
    [
        'id' => '3',
        'title' => 'Cooking Show',
        'category' => 'Lifestyle',
        'is_featured' => 'Y',
        'views' => '1500',
        'created_at' => '2025-05-12 14:00:00',
        'details' => json_encode([
            'platform' => 'Facebook',
            'quality' => 'HD'
        ])
    ],
    [
        'id' => '4',
        'title' => 'Tech Review',
        'category' => 'Technology',
        'is_featured' => 'Y',
        'views' => '2000',
        'created_at' => '2025-05-15 09:00:00',
        'details' => json_encode([
            'platform' => 'Twitch',
            'quality' => '4K'
        ])
    ],
];

// 1. Create the processor
$processor = new ArrayDataProcessor($data);

// 2. Configure field types and aliases
$processor
    ->setFieldType('id', 'int')
    ->setFieldType('views', 'int')
    ->setFieldType('created_at', 'datetime')
    ->setFieldType('details', 'json')
    ->setFieldAlias('views', 'view_count')
    ->setFieldAlias('created_at', 'published_at');

// 3. Select only specific fields for output
$processor->setFields(['id', 'title', 'view_count', 'category', 'published_at', 'details.platform']);

// 4. Add a filter (e.g., only featured Gaming category)
$processor->addFilter(function ($row) {
    return (
        $row['is_featured'] === 'Y' &&
        $row['category'] === 'Gaming'
    );
}, '__group__');

// 5. Sort and paginate
$processor
    ->addSortBy('views', 'desc')
    ->addSortBy('id', 'asc')
    ->setLimit(10)
    ->setOffset(0);

// 6. Group by category
$grouped = $processor->groupBy('category');
echo "\nGrouped by category:\n";
print_r($grouped);

// 7. Output processed data as array
echo "\nProcessed array:\n";
print_r($processor->toArray());

// 8. Output as JSON
// echo $processor->toJson();

// 9. Output as CSV
// echo $processor->toCsv();

// 10. Array utility examples

echo "\nCount: " . $processor->count() . "\n";
echo "First: " . json_encode($processor->first()) . "\n";
echo "Last: " . json_encode($processor->last()) . "\n";
echo "Random: " . json_encode($processor->random()) . "\n";
echo "Reverse: " . json_encode($processor->reverse()) . "\n";
echo "Shuffle: " . json_encode($processor->shuffle()) . "\n";
echo "Pluck titles: " . json_encode($processor->pluck('title')) . "\n";
echo "Filter (view_count > 1000): " . json_encode($processor->filter(function($row) {
    return $row['view_count'] > 1000;
})) . "\n";
echo "Map (double view_count): " . json_encode($processor->map(function($row) {
    $row['view_count'] = $row['view_count'] * 2;
    return $row;
})) . "\n";
echo "Sum view_count: " . $processor->sum('view_count') . "\n";
echo "Avg view_count: " . $processor->avg('view_count') . "\n";
echo "Min view_count: " . $processor->min('view_count') . "\n";
echo "Max view_count: " . $processor->max('view_count') . "\n";

echo "\nLog:\n";
print_r($processor->getLog());
