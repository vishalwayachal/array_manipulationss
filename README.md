# ArrayDataProcessor

A robust, production-grade PHP class for advanced array data manipulation, filtering, sorting, grouping, and transformation. Designed for flexibility, performance, and clarity, with full support for nested fields, type casting (including closures), aliasing, filtering, sorting, grouping, pagination, and array utilities.

---

## Features
- **Field casting** (including closures and nested/dot notation)
- **Field aliasing** (including nested fields)
- **Filtering** (AND/OR logic, edge cases, custom callbacks)
- **Multi-level sorting**
- **Pagination** (limit/offset)
- **Grouping** by any key (supports dot notation)
- **Array utility methods**: `count`, `first`, `last`, `random`, `reverse`, `shuffle`, `pluck`, `filter`, `map`, `sum`, `avg`, `min`, `max`
- **CSV/JSON export**
- **Operation logging**

---

## Installation
Copy `ArrayDataProcessor.php` (or just the `ArrayDataProcessor` class) into your project. No external dependencies required.

---

## Comprehensive Example Usage

```php
require 'ArrayDataProcessor.php';

// Example data array with various edge cases
$data = [
    [
        'id' => '1',
        'title' => 'Pro Gaming',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '1200',
        'created_at' => '2025-05-16 08:00:00',
        'tags' => 'fun,live,hd',
        'status' => '1',
        'rating' => '4.5',
        'details' => json_encode([
            'platform' => 'Twitch',
            'quality' => 'HD',
            'meta' => ['lang' => 'en']
        ]),
        'metadata' => json_encode(['foo' => 'bar']),
        'viewer_count' => '1000',
    ],
    // ... more rows ...
];

// 1. Create the processor
$processor = new ArrayDataProcessor($data);

// 2. Configure field types and aliases (including closures and edge cases)
$processor
    ->setFieldType('id', 'int')
    ->setFieldType('views', 'int')
    ->setFieldType('created_at', function ($v) { return (new DateTime($v))->format('Y-m-d H:i'); })
    ->setFieldType('details', 'json')
    ->setFieldType('details.platform', 'string') // nested field
    ->setFieldType('tags', function ($v) {
        if (is_array($v)) return array_map('trim', $v);
        if (is_string($v) && strlen($v)) return array_map('trim', explode(',', $v));
        return [];
    })
    ->setFieldType('status', function ($v) {
        $map = ['0' => 'inactive', '1' => 'active', '2' => 'suspended', '3' => 'pending'];
        return $map[$v] ?? 'unknown';
    })
    ->setFieldType('rating', function ($v) { return number_format((float)$v, 2, '.', ''); })
    ->setFieldType('metadata', function ($v) {
        $meta = json_decode($v, true);
        $meta['processed_at'] = date('Y-m-d H:i:s');
        return $meta;
    })
    ->setFieldType('viewer_count', function ($v) { return (int)$v; })
    ->setFieldAlias('views', 'view_count')
    ->setFieldAlias('created_at', 'published_at')
    ->setFieldAlias('details.platform', 'platform')
    ->setFieldAlias('viewer_count', 'audience');

// 3. Select only specific fields for output (including nested/dot notation and aliases)
$processor->setFields([
    'id', 'title', 'view_count', 'category', 'published_at', 'platform', 'tags', 'status', 'rating', 'audience', 'metadata',
]);

// 4. Add filters (AND/OR logic, edge cases)
$processor->setFilters([
    'is_featured' => ['type' => 'equals', 'value' => 'Y'],
    'category' => ['type' => 'in', 'value' => ['Gaming', 'Technology']],
]);
$processor->setFilterLogic('AND');

// 5. Sort and paginate
$processor
    ->addSortBy('view_count', 'desc')
    ->addSortBy('id', 'asc')
    ->setLimit(3)
    ->setOffset(0);

// 6. Group by a nested field (dot notation)
$groupedByPlatform = $processor->groupBy('platform');
echo "\nGrouped by platform (dot notation):\n";
print_r($groupedByPlatform);

// 7. Output processed data as array
echo "\nProcessed array:\n";
print_r($processor->toArray());

// 8. Output as JSON
echo "\nProcessed JSON:\n";
echo $processor->toJson(JSON_PRETTY_PRINT) . "\n";

// 9. Output as CSV
echo "\nProcessed CSV:\n";
echo $processor->toCsv() . "\n";

// 10. Array utility examples

echo "\nCount: " . $processor->count() . "\n";
echo "First: " . json_encode($processor->first()) . "\n";
echo "Last: " . json_encode($processor->last()) . "\n";
echo "Random: " . json_encode($processor->random()) . "\n";
echo "Reverse: " . json_encode($processor->reverse()) . "\n";
echo "Shuffle: " . json_encode($processor->shuffle()) . "\n";
echo "Pluck titles: " . json_encode($processor->pluck('title')) . "\n";
echo "Filter (view_count > 1000): " . json_encode($processor->filter(function ($row) {
    return $row['view_count'] > 1000;
})) . "\n";
echo "Map (double view_count): " . json_encode($processor->map(function ($row) {
    $row['view_count'] = $row['view_count'] * 2;
    return $row;
})) . "\n";
echo "Sum view_count: " . $processor->sum('view_count') . "\n";
echo "Avg view_count: " . $processor->avg('view_count') . "\n";
echo "Min view_count: " . $processor->min('view_count') . "\n";
echo "Max view_count: " . $processor->max('view_count') . "\n";

echo "\nLog of operations:\n";
print_r($processor->getLog());
```

---

## API Documentation & Examples

### Constructor

#### `__construct(array $data)`
- **Description:** Create a new processor instance with your data array.
- **Parameters:**
  - `$data` (array): The input data array (array of associative arrays).
- **Example:**
    ```php
    $processor = new ArrayDataProcessor($data);
    ```

---

### Field Type Casting

#### `setFieldType(string $field, string|callable $type)`
- **Description:** Set the type cast for a field. Supports built-in types (`int`, `float`, `string`, `json`, etc.) or a custom closure for advanced logic. Dot notation is supported for nested fields.
- **Parameters:**
  - `$field` (string): Field name (dot notation allowed)
  - `$type` (string|callable): Type or closure
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFieldType('id', 'int');
    $processor->setFieldType('created_at', function ($v) { return (new DateTime($v))->format('Y-m-d H:i'); });
    $processor->setFieldType('details', 'json');
    $processor->setFieldType('tags', function ($v) {
        if (is_array($v)) return array_map('trim', $v);
        if (is_string($v) && strlen($v)) return array_map('trim', explode(',', $v));
        return [];
    });
    ```

---

### Field Aliasing

#### `setFieldAlias(string $field, string $alias)`
- **Description:** Set an alias for a field. Dot notation is supported for nested fields.
- **Parameters:**
  - `$field` (string): Field name (dot notation allowed)
  - `$alias` (string): Alias name
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFieldAlias('views', 'view_count');
    $processor->setFieldAlias('created_at', 'published_at');
    $processor->setFieldAlias('details.platform', 'platform');
    ```

---

### Selecting Output Fields

#### `setFields(array $fields)`
- **Description:** Select only specific fields for output. Dot notation and aliases are supported.
- **Parameters:**
  - `$fields` (array): List of field names or aliases
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFields([
        'id', 'title', 'view_count', 'category', 'published_at', 'platform', 'tags', 'status', 'rating', 'audience', 'metadata',
    ]);
    ```

---

### Filtering

#### `setFilters(array $filterDefinitions)`
- **Description:** Add multiple filters at once. Each filter can specify type (`equals`, `in`, `like`, etc.) and value. Dot notation supported.
- **Parameters:**
  - `$filterDefinitions` (array): Associative array of field => [type, value]
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFilters([
        'is_featured' => ['type' => 'equals', 'value' => 'Y'],
        'category' => ['type' => 'in', 'value' => ['Gaming', 'Technology']],
    ]);
    $processor->setFilterLogic('AND');
    ```

#### `addFilter(callable $callback, string $name = null)`
- **Description:** Add a custom filter callback. Use for advanced or grouped logic.
- **Parameters:**
  - `$callback` (callable): Filter function
  - `$name` (string|null): Optional name
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->addFilter(function ($row) {
        return $row['is_featured'] === 'Y' && $row['category'] === 'Gaming';
    }, 'featured_gaming');
    ```

#### `setFilterLogic(string $logic)`
- **Description:** Set filter logic to 'AND' or 'OR'.
- **Parameters:**
  - `$logic` (string): 'AND' or 'OR'
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFilterLogic('AND');
    ```

---

### Sorting & Pagination

#### `addSortBy(string $field, string $direction = 'asc')`
- **Description:** Add a sort field. Multi-level sorting supported.
- **Parameters:**
  - `$field` (string): Field name
  - `$direction` (string): 'asc' or 'desc'
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->addSortBy('view_count', 'desc')->addSortBy('id', 'asc');
    ```

#### `setLimit(int $limit)` / `setOffset(int $offset)`
- **Description:** Set result limit and offset for pagination.
- **Parameters:**
  - `$limit` (int): Max results
  - `$offset` (int): Start index
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setLimit(3)->setOffset(0);
    ```

---

### Grouping

#### `groupBy(string $key)`
- **Description:** Group processed data by a key (dot notation supported).
- **Parameters:**
  - `$key` (string): Field name or alias
- **Returns:** `array` (grouped data)
- **Example:**
    ```php
    $groupedByPlatform = $processor->groupBy('platform');
    print_r($groupedByPlatform);
    ```

---

### Output Methods

#### `toArray()`
- **Description:** Get processed data as array.
- **Returns:** `array`
- **Example:**
    ```php
    print_r($processor->toArray());
    ```

#### `toJson(int $flags = 128)`
- **Description:** Get processed data as JSON.
- **Parameters:**
  - `$flags` (int): JSON encode flags (default: `JSON_PRETTY_PRINT`)
- **Returns:** `string`
- **Example:**
    ```php
    echo $processor->toJson(JSON_PRETTY_PRINT);
    ```

#### `toCsv(string $delimiter = ',', string $enclosure = '"')`
- **Description:** Get processed data as CSV.
- **Parameters:**
  - `$delimiter` (string): CSV delimiter
  - `$enclosure` (string): CSV enclosure
- **Returns:** `string`
- **Example:**
    ```php
    echo $processor->toCsv();
    ```

---

### Array Utility Methods

#### `count()`
- **Description:** Get count of processed records.
- **Returns:** `int`
- **Example:**
    ```php
    echo $processor->count();
    ```

#### `first()` / `last()`
- **Description:** Get first/last record.
- **Returns:** `array|null`
- **Example:**
    ```php
    echo json_encode($processor->first());
    echo json_encode($processor->last());
    ```

#### `random($num = 1)`
- **Description:** Get one or more random records.
- **Parameters:**
  - `$num` (int): Number of records
- **Returns:** `array|mixed|null`
- **Example:**
    ```php
    echo json_encode($processor->random());
    echo json_encode($processor->random(2));
    ```

#### `reverse()` / `shuffle()`
- **Description:** Get reversed/shuffled processed data.
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->reverse());
    echo json_encode($processor->shuffle());
    ```

#### `pluck($key)`
- **Description:** Pluck a single column from processed data.
- **Parameters:**
  - `$key` (string): Field name
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->pluck('title'));
    ```

#### `filter(callable $callback)`
- **Description:** Filter processed data with a callback.
- **Parameters:**
  - `$callback` (callable): Filter function
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->filter(function ($row) {
        return $row['view_count'] > 1000;
    }));
    ```

#### `map(callable $callback)`
- **Description:** Map processed data with a callback.
- **Parameters:**
  - `$callback` (callable): Map function
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->map(function ($row) {
        $row['view_count'] = $row['view_count'] * 2;
        return $row;
    }));
    ```

#### `sum($key)` / `avg($key)` / `min($key)` / `max($key)`
- **Description:** Sum, average, min, or max of a column in processed data.
- **Parameters:**
  - `$key` (string): Field name
- **Returns:** `float|int|mixed`
- **Example:**
    ```php
    echo $processor->sum('view_count');
    echo $processor->avg('view_count');
    echo $processor->min('view_count');
    echo $processor->max('view_count');
    ```

---

### Logging & Utilities

#### `getLog()`
- **Description:** Get a log of all operations performed.
- **Returns:** `array`
- **Example:**
    ```php
    print_r($processor->getLog());
    ```

#### `reset()`
- **Description:** Reset all filters, sorts, aliases, etc. (data remains).
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->reset();
    ```

#### `getCsvHeaders()`
- **Description:** Get the headers for CSV export.
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->getCsvHeaders());
    ```

---

## License
MIT
