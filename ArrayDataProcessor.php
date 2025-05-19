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
 * @author  VISHAL WAYACHAL
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
    protected $lastError = null; // Store last error message

    /**
     * Constructor
     *
     * Initializes the processor with the provided data array or object. Converts objects to arrays recursively.
     *
     * @param array|object $data The input data (array of associative arrays or objects).
     * @throws \InvalidArgumentException If data is not an array or object.
     *
     * @example
     *   $processor = new ArrayDataProcessor($data);
     */
    public function __construct($data)
    {
        if (is_object($data)) {
            $data = [$this->objectToArray($data)];
        } elseif (is_array($data)) {
            $data = array_map(function ($item) {
                return is_object($item) ? $this->objectToArray($item) : $item;
            }, $data);
        } else {
            throw new \InvalidArgumentException('Data must be an array or object');
        }
        $this->data = $data;
    }

    /**
     * Recursively convert an object to an array.
     *
     * @param object|array $obj The object or array to convert.
     * @return array The resulting array.
     *
     * @example
     *   $arr = $this->objectToArray($obj);
     */
    protected function objectToArray($obj)
    {
        if (is_array($obj)) {
            return array_map([$this, 'objectToArray'], $obj);
        } elseif (is_object($obj)) {
            return array_map([$this, 'objectToArray'], get_object_vars($obj));
        } else {
            return $obj;
        }
    }

    /**
     * Get the last error message encountered by the processor.
     *
     * @return string|null The last error message, or null if none.
     *
     * @example
     *   $error = $processor->getLastError();
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Set the type cast for a field. Supports built-in types (int, float, string, bool, json, date, datetime, enum) or a closure.
     *
     * @param string $field The field name (dot notation supported).
     * @param string|callable $type The type or closure to use for casting.
     * @return $this|false Returns $this for chaining or false on error.
     *
     * @example
     *   $processor->setFieldType('created_at', 'datetime');
     *   $processor->setFieldType('amount', function($v) { return $v / 100; });
     */
    public function setFieldType($field, $type)
    {
        if (!is_string($field) || $field === '') {
            $this->lastError = 'Invalid field name for setFieldType.';
            $this->log[] = $this->lastError;
            return false;
        }
        $this->casts[$field] = $type;
        return $this;
    }

    /**
     * Set an alias for a field. The alias will be used as the output key.
     *
     * @param string $field The original field name (dot notation supported).
     * @param string $alias The alias to use in output.
     * @return $this|false Returns $this for chaining or false on error.
     *
     * @example
     *   $processor->setFieldAlias('created_at', 'published_at');
     */
    public function setFieldAlias($field, $alias)
    {
        if (!is_string($field) || $field === '' || !is_string($alias) || $alias === '') {
            $this->lastError = 'Invalid field or alias for setFieldAlias.';
            $this->log[] = $this->lastError;
            return false;
        }
        $this->aliases[$field] = $alias;
        return $this;
    }

    /**
     * Set the output fields (supports dot notation for nested fields).
     *
     * @param array $fields List of fields to include in output.
     * @return $this|false Returns $this for chaining or false on error.
     *
     * @example
     *   $processor->setFields(['id', 'name', 'details.platform']);
     */
    public function setFields($fields)
    {
        if (!is_array($fields)) {
            $this->lastError = 'Fields must be an array in setFields.';
            $this->log[] = $this->lastError;
            return false;
        }
        $this->fields = $fields;
        return $this;
    }

    /**
     * Add additional fields to the output selection. This allows you to dynamically include more fields
     * in the result set after initial field selection. Useful for extending the output without resetting fields.
     *
     * @param array $keys Array of field names to add to the output (supports dot notation for nested fields).
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->setFields(['id', 'name']);
     *   $processor->selectKeys(['email', 'profile.age']);
     *   // Output will now include: id, name, email, profile.age
     */
    public function selectKeys($keys)
    {
        $this->fields = array_merge($this->fields, $keys);
        return $this;
    }

    /**
     * Set multiple filters at once using an array of filter definitions. Each filter can specify a type
     * (e.g., equals, greaterThan, in, like, etc.) and a value. This is a convenient way to apply multiple
     * field-based filters in a single call.
     *
     * @param array $filterDefinitions Associative array of filters, e.g. ['status' => ['type' => 'equals', 'value' => 'active']]
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->setFilters([
     *     'status' => ['type' => 'equals', 'value' => 'active'],
     *     'views' => ['type' => 'greaterThan', 'value' => 100]
     *   ]);
     *   // Only records with status 'active' and views > 100 will be included
     */
    public function setFilters($filterDefinitions)
    {
        foreach ($filterDefinitions as $field => $rule) {
            $this->addFilter(function ($row) use ($field, $rule) {
                $value = isset($row[$field]) ? $row[$field] : null;
                $expected = $rule['value'];
                switch ($rule['type']) {
                    case 'equals':
                        return $value == $expected;
                    case 'notEquals':
                        return $value != $expected;
                    case 'greaterThan':
                        return $value > $expected;
                    case 'greaterThanOrEqual':
                        return $value >= $expected;
                    case 'lessThan':
                        return $value < $expected;
                    case 'lessThanOrEqual':
                        return $value <= $expected;
                    case 'in':
                        return in_array($value, (array) $expected);
                    case 'notIn':
                        return !in_array($value, (array) $expected);
                    case 'like':
                        return strpos((string) $value, (string) $expected) !== false;
                    case 'startsWith':
                        return strpos((string) $value, (string) $expected) === 0;
                    case 'endsWith':
                        return substr((string) $value, -strlen((string) $expected)) === (string) $expected;
                    case 'between':
                        return $value >= $expected[0] && $value <= $expected[1];
                    case 'null':
                        return is_null($value);
                    case 'notNull':
                        return !is_null($value);
                    case 'empty':
                        return empty($value);
                    case 'notEmpty':
                        return !empty($value);
                    default:
                        return true;
                }
            }, $field);
        }
        return $this;
    }

    /**
     * Add a filter callback. Allows for custom filter logic using a closure.
     *
     * @param callable $callback The filter function.
     * @param string|null $name Optional name for the filter.
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->addFilter(function($row) { return $row['views'] > 1000; });
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
     * Remove a filter by name.
     *
     * @param string $name The filter name.
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->removeFilter('my_filter');
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);
        return $this;
    }

    /**
     * Set filter logic (AND/OR) for combining multiple filters.
     *
     * @param string $logic 'AND' or 'OR'.
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->setFilterLogic('OR');
     */
    public function setFilterLogic($logic)
    {
        $this->filterLogic = strtoupper($logic);
        return $this;
    }

    /**
     * Set all field aliases at once using an associative array.
     *
     * @param array $aliases Associative array of field => alias.
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->setAliases(['created_at' => 'published_at']);
     */
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Set all field casts at once using an associative array.
     *
     * @param array $casts Associative array of field => type/callback.
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->setCasts(['id' => 'int', 'created_at' => 'datetime']);
     */
    public function setCasts($casts)
    {
        $this->casts = $casts;
        return $this;
    }

    /**
     * Set enum mapping for fields. Used for 'enum' type casting.
     *
     * @param array $map Associative array of field => [value => label, ...].
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->setEnumMap(['status' => ['1' => 'Active', '0' => 'Inactive']]);
     */
    public function setEnumMap($map)
    {
        $this->enumMap = $map;
        return $this;
    }

    /**
     * Add a sort field for multi-level sorting.
     *
     * @param string $field The field to sort by.
     * @param string $direction 'asc' or 'desc'.
     * @return $this|false Returns $this for chaining or false on error.
     *
     * @example
     *   $processor->addSortBy('created_at', 'desc');
     */
    public function addSortBy($field, $direction = 'asc')
    {
        if (!is_string($field) || $field === '' || !in_array(strtolower($direction), ['asc', 'desc'])) {
            $this->lastError = 'Invalid field or direction for addSortBy.';
            $this->log[] = $this->lastError;
            return false;
        }
        $this->sortFields[] = ['field' => $field, 'direction' => strtolower($direction)];
        return $this;
    }

    /**
     * Set result limit for pagination.
     *
     * @param int $limit The maximum number of records to return.
     * @return $this|false Returns $this for chaining or false on error.
     *
     * @example
     *   $processor->setLimit(10);
     */
    public function setLimit($limit)
    {
        if (!is_int($limit) || $limit < 0) {
            $this->lastError = 'Limit must be a non-negative integer.';
            $this->log[] = $this->lastError;
            return false;
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set result offset for pagination.
     *
     * @param int $offset The number of records to skip.
     * @return $this|false Returns $this for chaining or false on error.
     *
     * @example
     *   $processor->setOffset(5);
     */
    public function setOffset($offset)
    {
        if (!is_int($offset) || $offset < 0) {
            $this->lastError = 'Offset must be a non-negative integer.';
            $this->log[] = $this->lastError;
            return false;
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Apply all registered filters to the data set.
     * 
     * This function processes the data array through all registered filters using the configured logic (AND/OR).
     * Supports both individual filters and grouped filters. For AND logic, all filters must pass.
     * For OR logic, at least one filter must pass.
     *
     * @param array $data The array of records to filter
     * @return array The filtered array of records
     *
     * @example
     * $filtered = $this->applyFilters([
     *     ['id' => 1, 'status' => 'active'],
     *     ['id' => 2, 'status' => 'inactive']
     * ]); // Returns only records matching filter conditions
     */
    protected function applyFilters($data)
    {
        if (empty($this->filters)) return $data;
        if (isset($this->filters['__group__'])) {
            $filtered = array_filter($data, function ($row) {
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
     * Apply multi-level sorting to the data set.
     * 
     * This function sorts the data array based on multiple sort fields and directions.
     * The sorting is performed using the _multiSortCompare function as a comparison callback.
     *
     * @param array $data The array of records to sort
     * @return array The sorted array of records
     *
     * @example
     * $sorted = $this->applySorting([
     *     ['name' => 'John', 'age' => 25],
     *     ['name' => 'Jane', 'age' => 30]
     * ]); // Returns records sorted by configured sort fields
     */
    protected function applySorting($data)
    {
        if (empty($this->sortFields)) return $data;
        usort($data, array($this, '_multiSortCompare'));
        return $data;
    }

    /**
     * Compare two records for multi-level sorting.
     * 
     * This function compares two records based on multiple sort fields and directions.
     * It supports ascending and descending order for each field and handles null values.
     *
     * @param array $a First record to compare
     * @param array $b Second record to compare
     * @return int -1 if $a < $b, 1 if $a > $b, 0 if equal
     *
     * @example
     * // With sort fields configured as:
     * // [['field' => 'age', 'direction' => 'desc'], ['field' => 'name', 'direction' => 'asc']]
     * $result = $this->_multiSortCompare(
     *     ['name' => 'John', 'age' => 25],
     *     ['name' => 'Jane', 'age' => 30]
     * ); // Returns 1 because 25 < 30 in descending order
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
     * Apply pagination to the data set using limit and offset.
     * 
     * This function slices the data array based on the configured limit and offset values.
     * When limit is negative, returns all data. Otherwise returns a subset of records.
     *
     * @param array $data The array of records to paginate
     * @return array The paginated subset of records
     *
     * @example
     * // With limit=2 and offset=1
     * $paginated = $this->applyPagination([
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Jane'],
     *     ['id' => 3, 'name' => 'Bob']
     * ]); // Returns [['id' => 2, 'name' => 'Jane'], ['id' => 3, 'name' => 'Bob']]
     */
    protected function applyPagination($data)
    {
        return $this->limit < 0 ? $data : array_slice($data, $this->offset, $this->limit);
    }

    /**
     * Get a value from a nested array structure using dot notation.
     * 
     * This function traverses a nested array using dot notation and supports wildcards.
     * It can handle deep nesting, numeric indices, and wildcard (*) patterns.
     * For wildcard patterns, it always returns a numerically indexed array.
     *
     * @param array $array The array to search in
     * @param string $key The dot notation path (e.g., 'user.addresses.*.city')
     * @return mixed|null|array The found value(s) or null if not found
     *
     * @example
     * $value = $this->getNestedValue([
     *     'user' => [
     *         'addresses' => [
     *             ['city' => 'New York'],
     *             ['city' => 'London']
     *         ]
     *     ]
     * ], 'user.addresses.*.city'); // Returns ['New York', 'London']
     */
    protected function getNestedValue($array, $key)
    {
        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : null;
        }
        $parts = explode('.', $key);
        $value = $array;
        foreach ($parts as $i => $part) {
            if ($part === '*') {
                // Wildcard: collect all values at this level
                if (!is_array($value)) return null;
                $results = [];
                foreach ($value as $item) {
                    $subkey = implode('.', array_slice($parts, $i + 1));
                    $res = $this->getNestedValue($item, $subkey);
                    if (is_array($res) && $subkey && strpos($subkey, '*') !== false) {
                        // Flatten nested arrays from deeper wildcards
                        foreach ($res as $r) $results[] = $r;
                    } elseif ($res !== null) {
                        $results[] = $res;
                    }
                }
                // Ensure pure numeric array
                return array_values($results);
            } elseif (is_numeric($part)) {
                // Numeric index for list
                if (is_array($value) && isset($value[(int)$part])) {
                    $value = $value[(int)$part];
                } else {
                    return null;
                }
            } elseif (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return null;
            }
        }
        return $value;
    }

    /**
     * Process and transform data by applying type casting and field aliasing.
     * 
     * This function handles type casting of fields and field name aliasing for the output.
     * It also flattens nested arrays and supports JSON field decoding.
     * When fields are specified, only those fields are included in the output.
     *
     * @param array $data The array of records to process
     * @return array The processed records with cast values and aliased field names
     *
     * @example
     * $processed = $this->castAndAlias([
     *     ['id' => '1', 'created' => '2023-01-01', 'data' => '{"name":"John"}']
     * ]); // Returns records with proper types and aliases applied
     */
    protected function castAndAlias($data)
    {
        if (!empty($this->fields) || !empty($this->aliases)) {
            return array_map(function ($item) {
                $projected = [];
                $decodedParents = [];
                if (!empty($this->fields)) {
                    foreach ($this->fields as $field) {
                        $lookupField = array_search($field, $this->aliases, true);
                        $lookupField = $lookupField !== false ? $lookupField : $field;
                        if (strpos($lookupField, '.') !== false) {
                            $parts = explode('.', $lookupField);
                            $parent = $parts[0];
                            if (isset($this->casts[$parent]) && $this->casts[$parent] === 'json' && isset($item[$parent]) && is_string($item[$parent]) && !isset($decodedParents[$parent])) {
                                $item[$parent] = json_decode($item[$parent], true);
                                $decodedParents[$parent] = true;
                            }
                        }
                    }
                    foreach ($this->fields as $field) {
                        $lookupField = array_search($field, $this->aliases, true);
                        $lookupField = $lookupField !== false ? $lookupField : $field;
                        $value = $this->getNestedValue($item, $lookupField);
                        $cast = isset($this->casts[$lookupField]) ? $this->casts[$lookupField] : null;
                        $value = $this->castValue($value, $cast, $lookupField);
                        // Ensure pure array for numeric-keyed arrays
                        if (is_array($value) && $this->isPureArray($value)) {
                            $value = array_values($value);
                        }
                        $outputKey = $field;
                        $projected[$outputKey] = $value;
                    }
                } else {
                    foreach ($item as $key => $value) {
                        $cast = isset($this->casts[$key]) ? $this->casts[$key] : null;
                        $value = $this->castValue($value, $cast, $key);
                        if (is_array($value) && $this->isPureArray($value)) {
                            $value = array_values($value);
                        }
                        $outputKey = isset($this->aliases[$key]) ? $this->aliases[$key] : $key;
                        $projected[$outputKey] = $value;
                    }
                }
                return $projected;
            }, $data);
        }
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
     * Check if an array is associative.
     * 
     * This function determines whether the given array uses string keys or non-sequential numeric keys.
     * An array is considered associative if its keys are not a sequential range from 0 to count-1.
     *
     * @param array $array The array to check for associative keys
     * @return bool True if array is associative, false if sequential numeric
     *
     * @example
     *   $isAssoc = $this->isAssoc(['foo' => 1, 'bar' => 2]); // Returns true
     *   $isAssoc = $this->isAssoc([1, 2, 3]); // Returns false
     */
    protected function isAssoc($array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Check if an array is a pure numerically indexed array.
     *
     * This function verifies if the given array has consecutive numeric keys starting from 0.
     * It helps distinguish between associative arrays and pure indexed arrays.
     *
     * @param array $array The array to check for pure numeric indexing
     * @return bool True if array has consecutive numeric keys from 0, false otherwise
     *
     * @example
     *   $isPure = $this->isPureArray([1, 2, 3]); // Returns true
     *   $isPure = $this->isPureArray(['a' => 1]); // Returns false
     */
    protected function isPureArray($array)
    {
        if (!is_array($array)) return false;
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i++) return false;
        }
        return true;
    }

    /**
     * Flatten and process a nested array row into a single-level associative array.
     * 
     * This function converts a nested array structure into a flat array using dot notation.
     * It applies field aliases and type casting during the flattening process.
     * Handles both associative arrays and specified field selections.
     *
     * @param array $row The row data to flatten and process
     * @param string $prefix Current key prefix for nested levels (used recursively)
     * @return array The flattened and processed array
     *
     * @example
     * $flat = $this->flattenAndProcessRow([
     *     'user' => ['name' => 'John', 'age' => 25],
     *     'status' => 'active'
     * ]); // Returns ['user.name' => 'John', 'user.age' => 25, 'status' => 'active']
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
     * Cast a value to a specified type or transform it using a callback function.
     * 
     * This function supports built-in type casting (int, float, string, bool, json, date, datetime, enum)
     * and custom transformations via callback functions. For enum types, uses the enumMap for value mapping.
     *
     * @param mixed $value The value to cast
     * @param string|callable|null $type The target type or callback function
     * @param string $key The field key (used for enum mapping)
     * @return mixed The cast/transformed value
     *
     * @example
     * $cast = $this->castValue('123', 'int'); // Returns 123 (integer)
     * $cast = $this->castValue('2023-05-19', 'date'); // Returns '2023-05-19'
     * $cast = $this->castValue('1', 'enum', 'status'); // Returns mapped value from enumMap
     */
    protected function castValue($value, $type, $key = '')
    {
        if (!$type) return $value;
        if (is_callable($type)) return $type($value);
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'bool':
                return (bool)$value;
            case 'json':
                return json_decode($value, true);
            case 'date':
                return date('Y-m-d', strtotime($value));
            case 'datetime':
            case 'timestamp':
                return date('Y-m-d H:i:s', strtotime($value));
            case 'enum':
                return isset($this->enumMap[$key][$value]) ? $this->enumMap[$key][$value] : $value;
            default:
                return $value;
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
        // Ensure pure numeric array for top-level result
        return array_values($data);
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
            $output[] = implode($delimiter, array_map(function ($v) use ($enclosure) {
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
     * @return array|false
     */
    public function groupBy($key)
    {
        if (!is_string($key) || $key === '') {
            $this->lastError = 'Invalid key for groupBy.';
            $this->log[] = $this->lastError;
            return false;
        }
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
        return count($data) ? $data[count($data) - 1] : null;
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
     * Get reversed processed data.
     * Returns the processed data in reverse order.
     *
     * @return array The reversed processed data.
     *
     * @example
     *   $reversed = $processor->reverse();
     */
    public function reverse()
    {
        return array_reverse($this->process());
    }

    /**
     * Get shuffled processed data.
     * Returns the processed data in random order.
     *
     * @return array The shuffled processed data.
     *
     * @example
     *   $shuffled = $processor->shuffle();
     */
    public function shuffle()
    {
        $data = $this->process();
        shuffle($data);
        return $data;
    }

    /**
     * Pluck a single column from processed data.
     * Extracts the values of a single column from all processed records.
     *
     * @param string $key The field name to pluck (dot notation supported).
     * @return array Array of values for the specified field.
     *
     * @example
     *   $titles = $processor->pluck('title');
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
     * Filter processed data with a callback.
     * Returns all records for which the callback returns true.
     *
     * @param callable $callback The filter function. Receives each row as argument.
     * @return array Array of filtered records.
     *
     * @example
     *   $filtered = $processor->filter(function($row) { return $row['views'] > 1000; });
     */
    public function filter(callable $callback)
    {
        $data = $this->process();
        return array_values(array_filter($data, $callback));
    }

    /**
     * Map processed data with a callback.
     * Applies the callback to each record and returns the results.
     *
     * @param callable $callback The map function. Receives each row as argument.
     * @return array Array of mapped records.
     *
     * @example
     *   $doubled = $processor->map(function($row) { $row['views'] *= 2; return $row; });
     */
    public function map(callable $callback)
    {
        $data = $this->process();
        return array_map($callback, $data);
    }

    /**
     * Sum a column in processed data.
     * Calculates the sum of the specified field for all processed records.
     *
     * @param string $key The field name to sum.
     * @return float|int The sum of the field values.
     *
     * @example
     *   $total = $processor->sum('views');
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
     * Average of a column in processed data.
     * Calculates the average value of the specified field for all processed records.
     *
     * @param string $key The field name to average.
     * @return float|int The average value.
     *
     * @example
     *   $average = $processor->avg('views');
     */
    public function avg($key)
    {
        $data = $this->process();
        $count = count($data);
        return $count ? $this->sum($key) / $count : 0;
    }

    /**
     * Minimum value of a column in processed data.
     * Finds the minimum value of the specified field among all processed records.
     *
     * @param string $key The field name to check.
     * @return mixed The minimum value, or null if no values.
     *
     * @example
     *   $min = $processor->min('views');
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
     * Maximum value of a column in processed data.
     * Finds the maximum value of the specified field among all processed records.
     *
     * @param string $key The field name to check.
     * @return mixed The maximum value, or null if no values.
     *
     * @example
     *   $max = $processor->max('views');
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

    /**
     * Expand each record by a nested list, duplicating parent fields and mapping nested fields to top-level fields.
     * Useful for flattening nested arrays (e.g., accents) into a flat structure for export or further processing.
     *
     * @param string $listKey The key of the nested list to expand (e.g., 'accents').
     * @param array $mapping Mapping of output field => nested field (e.g., ['accent_name' => 'accent_name', ...]).
     * @return $this Returns the current instance for method chaining.
     *
     * @example
     *   $processor->expandByNestedList('accents', [
     *     'accent_name' => 'accent_name',
     *     'accent_id' => 'id',
     *     'preview_url' => 'preview_url'
     *   ]);
     */
    public function expandByNestedList($listKey, $mapping)
    {
        $expanded = [];
        foreach ($this->data as $row) {
            if (!isset($row[$listKey]) || !is_array($row[$listKey]) || empty($row[$listKey])) {
                continue; // skip records with no accents
            }
            foreach ($row[$listKey] as $nested) {
                $newRow = $row;
                unset($newRow[$listKey]);
                foreach ($mapping as $outKey => $nestedKey) {
                    $newRow[$outKey] = isset($nested[$nestedKey]) ? $nested[$nestedKey] : null;
                }
                $expanded[] = $newRow;
            }
        }
        $this->data = $expanded;
        return $this;
    }
}

// --- Comprehensive Example Usage ---

