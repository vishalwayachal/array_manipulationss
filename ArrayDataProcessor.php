<?php


//REF :: https://gist.github.com/vishalwayachal/b9fee18d2f11b138d2362176cb68ecfb
/**
 * ArrayDataProcessor - A flexible data processing utility for array manipulation for Peeks Platform
 * 
 * Production-ready data processing utility that handles array manipulation with proper logging,
 * error handling, and performance optimization for the Peeks platform.
 * 
 * This class provides functionality for filtering, sorting, and transforming arrays of data
 * with support for custom filters, field aliasing, type casting, and multiple output formats.
 * 
 * Features:
 * - Custom filter callbacks
 * - AND/OR filter logic
 * - Field type casting
 * - Field aliasing
 * - Multiple output formats (array, JSON, CSV)
 * - Sorting and pagination
 * 
 * Example Usage:
 * ```php
 * // Sample data array with rich stream information
 * $data = [
 *     [
 *         'stream_id' => '1001',
 *         'user_id' => '5001',
 *         'title' => 'Gaming Stream',
 *         'viewer_count' => '1500',
 *         'is_featured' => 'Y',
 *         'is_adult' => 'N',
 *         'rating' => '4.50',
 *         'amount' => '2500', // Amount in cents
 *         'created_at' => '2025-05-16 08:00:00',
 *         'status' => '1',
 *         'tags' => 'gaming,live,esports',
 *         'metadata' => json_encode([
 *             'device' => 'mobile',
 *             'quality' => 'HD',
 *             'game' => 'Fortnite'
 *         ])
 *     ],
 *     [
 *         'stream_id' => '1002',
 *         'user_id' => '5002',
 *         'title' => 'Cooking Show',
 *         'viewer_count' => '800',
 *         'is_featured' => 'N',
 *         'is_adult' => 'N',
 *         'rating' => '4.75',
 *         'amount' => '1500',
 *         'created_at' => '2025-05-16 09:30:00',
 *         'status' => '1',
 *         'tags' => 'cooking,recipe,food',
 *         'metadata' => json_encode([
 *             'device' => 'desktop',
 *             'quality' => '4K',
 *             'cuisine' => 'Italian'
 *         ])
 *     ]
 * ];
 * 
 * // Initialize processor
 * $processor = new ArrayDataProcessor($data);
 * // Configure type casting for different fields
 * $processor
 *     // Basic type casting
 *     ->setFieldType('stream_id', 'int')
 *     ->setFieldType('user_id', 'int')
 *     ->setFieldType('viewer_count', 'int')
 *     // Convert amount from cents to dollars with formatting
 *     ->setFieldType('amount', function($value) {
 *         return number_format($value / 100, 2, '.', '');
 *     })
 *     // Convert rating to float and ensure 2 decimal places
 *     ->setFieldType('rating', function($value) {
 *         return number_format((float)$value, 2, '.', '');
 *     })
 * 
 *     // Parse date strings into DateTime objects
 *     ->setFieldType('created_at', function($value) {
 *         return new \DateTime($value);
 *     })
 * 
 *     // Convert status codes to meaningful strings
 *     ->setFieldType('status', function($value) {
 *         $statusMap = [
 *             '0' => 'inactive',
 *             '1' => 'active',
 *             '2' => 'suspended',
 *             '3' => 'pending'
 *         ];
 *         return $statusMap[$value] ?? 'unknown';
 *     })
 * 
 *     // Convert comma-separated tags to arrays
 *     ->setFieldType('tags', function($value) {
 *         return array_map('trim', explode(',', $value));
 *     })
 * 
 *     // Parse JSON metadata and add processing timestamp
 *     ->setFieldType('metadata', function($value) {
 *         $metadata = json_decode($value, true);
 *         $metadata['processed_at'] = date('Y-m-d H:i:s');
 *         return $metadata;
 *     });
 * 
 * // Set up comprehensive filters
 * $processor->setFilters([
 *     'viewer_count' => [
 *         'type' => 'greaterThan',
 *         'value' => 1000
 *     ],
 *     'is_featured' => [
 *         'type' => 'equals',
 *         'value' => 'Y'
 *     ],
 *     'is_adult' => [
 *         'type' => 'equals',
 *         'value' => 'N'
 *     ],
 *     'rating' => [
 *         'type' => 'greaterThan',
 *         'value' => '4.00'
 *     ],
 *     'status' => [
 *         'type' => 'equals',
 *         'value' => 'active'
 *     ]
 * ]);
 * 
 * // Set field aliases for cleaner output
 * $processor
 *     ->setFieldAlias('viewer_count', 'viewers')
 *     ->setFieldAlias('is_featured', 'featured')
 *     ->setFieldAlias('created_at', 'streamDate');
 * 
 * // Select fields to include in output
 * $processor->setFields([
 *     'stream_id',
 *     'title',
 *     'viewers',
 *     'featured',
 *     'rating',
 *     'amount',
 *     'streamDate',
 *     'tags',
 *     'metadata'
 * ]);
 * 
 * // Process data and get results
 * $result = $processor->process();
 * ```
 * 
 * @package Peeks\Lib
 */
class ArrayDataProcessor
{
    /** @var array The source data to process */
    protected $data;

    /** @var array Filter conditions to apply */
    protected $filters = [];

    /** @var array Sorting criteria */
    protected $sort = [];

    /** @var int|null Maximum number of items to return */
    protected $limit = null;

    /** @var int Number of items to skip */
    protected $offset = 0;

    /** @var array Fields to include in output */
    protected $fields = [];

    /** @var array Field name mappings */
    protected $fieldAliases = [];

    /** @var array Custom filter callbacks */
    protected $customFilters = [];

    /** @var string Logic to apply between filters (AND/OR) */
    protected $filterLogic = 'AND';

    /** @var string Output format (array/json/csv) */
    protected $outputFormat = 'array';

    /** @var array Type casting configuration */
    protected $typeMap = [];

    /** @var array Custom type casting callbacks */
    protected $customTypeMap = [];



    /**
     * Constructor
     * 
     * @param array $data The source data to process
     */
    public function __construct(array $data)
    {
        $this->data = $data;
       

        // Log initialization with both PSR-3 and RV logger
        $logContext = [
            'count' => count($data),
            'memory' => memory_get_usage(true),
            'dataType' => gettype(reset($data)),
        ];

     
    }

    /**
     * Sets the filter conditions for the data processing
     * 
     * @param array $filters Associative array of filter conditions
     * @return self
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Sets the sorting criteria for the data processing
     * 
     * @param array $sort Associative array of sort fields and directions
     * @return self
     */
    public function setSort(array $sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * Sets the maximum number of items to return
     * 
     * @param int|null $limit Maximum number of items
     * @return self
     */
    public function setLimit(?int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the number of items to skip
     * 
     * @param int|null $offset Number of items to skip
     * @return self
     */
    public function setOffset(?int $offset)
    {
        $this->offset = $offset ?? 0;
        return $this;
    }

    /**
     * Sets the fields to be included in the output
     * 
     * @param array $fields List of field names to include
     * @return self
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Sets the filter logic to AND or OR
     * 
     * @param string $logic Either 'AND' or 'OR'
     * @return self
     * @throws \InvalidArgumentException If logic is invalid
     */
    public function setFilterLogic(string $logic)
    {
        $logic = strtoupper($logic);
        if (!in_array($logic, ['AND', 'OR'])) {
            throw new \InvalidArgumentException('Filter logic must be either AND or OR');
        }
        $this->filterLogic = $logic;
        return $this;
    }

    /**
     * Adds a custom filter callback for a field
     * 
     * @param string $field Field name
     * @param callable $callback Filter callback function
     * @return self
     */
    public function addCustomFilter(string $field, callable $callback)
    {
        $this->customFilters[$field] = $callback;
        return $this;
    }

    /**
     * Sets an alias for a field name in the output
     * 
     * @param string $originalField Original field name
     * @param string $alias New field name
     * @return self
     */
    public function setFieldAlias(string $originalField, string $alias)
    {
        $this->fieldAliases[$originalField] = $alias;
    
        return $this;
    }

    /**
     * Sets the output format
     * 
     * @param string $format One of 'array', 'json', or 'csv'
     * @return self
     * @throws \InvalidArgumentException If format is invalid
     */
    public function setOutputFormat(string $format)
    {
        if (!in_array($format, ['array', 'json', 'csv'])) {
            throw new \InvalidArgumentException('Unsupported output format');
        }
        $this->outputFormat = $format;
        return $this;
    }

    /**
     * Sets the type casting for a field
     * 
     * @param string $field Field name
     * @param string|callable $type One of 'int', 'float', 'bool', 'string', 'array' or a callback function
     * @return self
     * @throws \InvalidArgumentException If type is invalid
     */
    public function setFieldType(string $field, $type)
    {
        if (is_callable($type)) {
            $this->customTypeMap[$field] = $type;
            return $this;
        }

        $validTypes = ['int', 'integer', 'float', 'double', 'bool', 'boolean', 'string', 'array'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Unsupported type: {$type}");
        }
        $this->typeMap[$field] = $type;       
        return $this;
    }

    /**
     * Process the data with all configured operations
     * 
     * @return mixed Processed data in the configured output format
     * @throws \Exception If any processing step fails
     */
    public function process()
    {
        $startTime = microtime(true);

        try {
            // Log processing start
        

            // Apply type casting first to ensure all values are properly cast
            $result = $this->applyTypeCasting($this->data);

            // Continue with the regular processing pipeline
            if (!empty($this->filters)) {
                $filterStartTime = microtime(true);
                $result = $this->applyFilters($result);
              
            }

            if (!empty($this->sort)) {
                $sortStartTime = microtime(true);
                $result = $this->applySort($result);
              
            }

            // Apply pagination
            if ($this->limit !== null || $this->offset > 0) {
                $result = $this->applyPagination($result);
            }

            // Project fields if needed
            if (!empty($this->fields) || !empty($this->fieldAliases)) {
                $result = $this->projectFields($result);
            }

            // Format final output
            $output = $this->formatOutput($result);

                     return $output;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Applies all configured filters to the data set
     * 
     * @return array Filtered data
     */
    protected function applyFilters($data)
    {

        return array_filter($data, function ($item) {
            $results = [];

            foreach ($this->filters as $key => $filter) {
                try {
                    // Check for custom filter first
                    if (isset($this->customFilters[$key])) {
                        $results[] = call_user_func($this->customFilters[$key], $item[$key] ?? null, $item);
                        continue;
                    }

                    $value = $item[$key] ?? null;

                    // Note: Type casting has already been applied in applyTypeCasting
                    $type = $filter['type'] ?? 'equals';
                    $expected = $filter['value'] ?? null;
                    $caseSensitive = $filter['case_sensitive'] ?? false;

                    switch ($type) {
                        case 'equals':
                            if ($caseSensitive) {
                                if ($value !== $expected) return false;
                            } else {
                                if (strtolower((string)$value) !== strtolower((string)$expected)) return false;
                            }
                            break;

                        case 'notEquals':
                            if ($caseSensitive) {
                                if ($value === $expected) return false;
                            } else {
                                if (strtolower((string)$value) === strtolower((string)$expected)) return false;
                            }
                            break;

                        case 'contains':
                            if ($caseSensitive) {
                                if (strpos((string)$value, (string)$expected) === false) return false;
                            } else {
                                if (stripos((string)$value, (string)$expected) === false) return false;
                            }
                            break;

                        case 'startsWith':
                            if ($caseSensitive) {
                                if (strpos((string)$value, (string)$expected) !== 0) return false;
                            } else {
                                if (stripos((string)$value, (string)$expected) !== 0) return false;
                            }
                            break;

                        case 'endsWith':
                            $length = strlen((string)$expected);
                            if ($caseSensitive) {
                                if (substr((string)$value, -$length) !== (string)$expected) return false;
                            } else {
                                if (strtolower(substr((string)$value, -$length)) !== strtolower((string)$expected)) return false;
                            }
                            break;

                        case 'in':
                            if (!in_array($value, (array)$expected, true)) return false;
                            break;

                        case 'notIn':
                            if (in_array($value, (array)$expected, true)) return false;
                            break;

                        case 'greaterThan':
                            if ($value <= $expected) return false;
                            break;

                        case 'lessThan':
                            if ($value >= $expected) return false;
                            break;

                        case 'between':
                            if (!is_array($expected) || count($expected) !== 2) return false;
                            if ($value < $expected[0] || $value > $expected[1]) return false;
                            break;

                        case 'before':
                            if ($value >= $expected) return false;
                            break;

                        case 'after':
                            if ($value <= $expected) return false;
                            break;

                        case 'isNull':
                            if (!is_null($value)) return false;
                            break;

                        case 'isNotNull':
                            if (is_null($value)) return false;
                            break;

                        default:
                            $results[] = false;
                            break;
                    }

                    $results[] = true;
                } catch (\Exception $e) {                   
                    $results[] = false;
                }
            }

            // Apply AND/OR logic to results
            if (empty($results)) {
                return true; // No filters applied
            }

            return $this->filterLogic === 'AND'
                ? !in_array(false, $results, true)  // All must be true
                : in_array(true, $results, true);   // At least one must be true
        });
    }

    protected function applySort(array $data)
    {
        if (!empty($this->sort)) {
            usort($data, function ($a, $b) {
                foreach ($this->sort as $key => $direction) {
                    $valA = $a[$key] ?? null;
                    $valB = $b[$key] ?? null;

                    // Type casting has already been applied at this point
                    $cmp = is_string($valA) && is_string($valB)
                        ? strcasecmp($valA, $valB)
                        : ($valA <=> $valB);

                    if ($cmp !== 0) {
                        return strtolower($direction) === 'desc' ? -$cmp : $cmp;
                    }
                }
                return 0;
            });
        }
        return $data;
    }

    protected function applyPagination(array $data)
    {
        if ($this->limit !== null) {
            return array_slice(array_values($data), $this->offset, $this->limit);
        }
        return array_values($data);
    }

    protected function projectFields(array $data)
    {
        if (!empty($this->fields) || !empty($this->fieldAliases)) {
            return array_map(function ($item) {
                $projected = [];

                // Project specified fields
                if (!empty($this->fields)) {
                    $projected = array_intersect_key($item, array_flip($this->fields));
                } else {
                    $projected = $item;
                }

                // Apply field aliases
                foreach ($this->fieldAliases as $original => $alias) {
                    if (isset($projected[$original])) {
                        $projected[$alias] = $projected[$original];
                        unset($projected[$original]);
                    }
                }

                return $projected;
            }, $data);
        }
        return $data;
    }

    protected function formatOutput(array $data)
    {
        switch ($this->outputFormat) {
            case 'json':
                return json_encode($data);
            case 'csv':
                if (empty($data)) {
                    return '';
                }
                $output = fopen('php://temp', 'r+');
                // Write headers
                fputcsv($output, array_keys(reset($data)));
                // Write data
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
                rewind($output);
                $csv = stream_get_contents($output);
                fclose($output);
                return $csv;
            default:
                return $data;
        }
    }

    protected function castValue($value, string $type, string $field = null)
    {
        // Check for field-specific custom casting first
        if ($field !== null && isset($this->customTypeMap[$field])) {
            try {
                return call_user_func($this->customTypeMap[$field], $value);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        // Handle standard type casting
        try {
            switch ($type) {
                case 'int':
                case 'integer':
                    return (int)$value;
                case 'float':
                case 'double':
                    return (float)$value;
                case 'bool':
                case 'boolean':
                    return (bool)$value;
                case 'string':
                    return (string)$value;
                case 'array':
                    return (array)$value;
                default:
                    throw new \InvalidArgumentException("Unsupported type: {$type}");
            }
        } catch (\Exception $e) {
      
            throw $e;
        }
    }

    /**
     * Apply type casting to all configured fields in the dataset
     * 
     * @param array $data The data to process
     * @return array Processed data with type casting applied
     */
    protected function applyTypeCasting(array $data)
    {
        if (empty($this->typeMap) && empty($this->customTypeMap)) {
            return $data;
        }

        return array_map(function ($item) {
            $processed = $item;

            // Apply standard type casting first
            foreach ($this->typeMap as $field => $type) {
                if (isset($item[$field])) {
                    $processed[$field] = $this->castValue($item[$field], $type, $field);
                }
            }

            // Apply custom type casting
            foreach ($this->customTypeMap as $field => $callback) {
                if (isset($item[$field])) {
                    $processed[$field] = call_user_func($callback, $item[$field]);
                }
            }

            return $processed;
        }, $data);
    }

    
}
