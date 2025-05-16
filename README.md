# ArrayDataProcessor

A robust, production-grade PHP class for advanced array data manipulation, filtering, sorting, grouping, and transformation.

## Features
- Field casting and aliasing
- Filtering (with AND/OR logic)
- Multi-level sorting
- Pagination (limit/offset)
- Grouping by any key (supports dot notation)
- Array utility methods: `count`, `first`, `last`, `random`, `reverse`, `shuffle`, `pluck`, `filter`, `map`, `sum`, `avg`, `min`, `max`
- CSV/JSON export
- Logging of all operations

## Installation
Copy `ArrayDataProcessor.php` into your project. No external dependencies required.

## Usage Examples

### 1. Basic Setup
```php
require 'ArrayDataProcessor.php';

$data = [
    [
        'id' => '1',
        'title' => 'Pro Gaming',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '1200',
        'created_at' => '2025-05-16 08:00:00',
        'details' => json_encode(['platform' => 'Twitch', 'quality' => 'HD'])
    ],
    // ... more rows ...
];

$processor = new ArrayDataProcessor($data);
```

### 2. Configure Field Types and Aliases
```php
$processor
    ->setFieldType('id', 'int') // Cast 'id' as integer
    ->setFieldType('views', 'int') // Cast 'views' as integer
    ->setFieldType('created_at', 'datetime') // Cast 'created_at' as datetime string
    ->setFieldType('details', 'json') // Parse 'details' as JSON
    ->setFieldAlias('views', 'view_count') // Alias 'views' to 'view_count'
    ->setFieldAlias('created_at', 'published_at'); // Alias 'created_at' to 'published_at'
```

### 3. Select Output Fields
```php
$processor->setFields(['id', 'title', 'view_count', 'category', 'published_at', 'details.platform']);
```

### 4. Filtering
```php
// Add a filter for featured Gaming category
$processor->addFilter(function ($row) {
    return (
        $row['is_featured'] === 'Y' &&
        $row['category'] === 'Gaming'
    );
}, '__group__');
```

### 5. Sorting and Pagination
```php
$processor
    ->addSortBy('views', 'desc') // Sort by views descending
    ->addSortBy('id', 'asc')     // Then by id ascending
    ->setLimit(10)               // Limit to 10 results
    ->setOffset(0);              // Start from the first result
```

### 6. Grouping
```php
$grouped = $processor->groupBy('category'); // Group by category
print_r($grouped);
```

### 7. Output Processed Data
```php
print_r($processor->toArray()); // Output as array
echo $processor->toJson();      // Output as JSON
echo $processor->toCsv();       // Output as CSV
```

### 8. Array Utility Methods

#### count()
Returns the number of processed records.
```php
echo $processor->count();
```

#### first()
Returns the first record.
```php
echo json_encode($processor->first());
```

#### last()
Returns the last record.
```php
echo json_encode($processor->last());
```

#### random($num = 1)
Returns one or more random records.
```php
echo json_encode($processor->random());      // One random record
echo json_encode($processor->random(2));     // Two random records
```

#### reverse()
Returns the processed data in reverse order.
```php
echo json_encode($processor->reverse());
```

#### shuffle()
Returns the processed data in random order.
```php
echo json_encode($processor->shuffle());
```

#### pluck($key)
Returns an array of values for a single column.
```php
echo json_encode($processor->pluck('title'));
```

#### filter(callable $callback)
Returns records matching a custom filter.
```php
echo json_encode($processor->filter(function($row) {
    return $row['view_count'] > 1000;
}));
```

#### map(callable $callback)
Returns records after applying a transformation.
```php
echo json_encode($processor->map(function($row) {
    $row['view_count'] = $row['view_count'] * 2;
    return $row;
}));
```

#### sum($key)
Returns the sum of a column.
```php
echo $processor->sum('view_count');
```

#### avg($key)
Returns the average of a column.
```php
echo $processor->avg('view_count');
```

#### min($key)
Returns the minimum value of a column.
```php
echo $processor->min('view_count');
```

#### max($key)
Returns the maximum value of a column.
```php
echo $processor->max('view_count');
```

### 9. Logging
Get a log of all operations performed.
```php
print_r($processor->getLog());
```

### 10. Resetting Configuration
Reset all filters, sorts, aliases, etc. (data remains).
```php
$processor->reset();
```

### 11. CSV Headers
Get the headers for CSV export.
```php
echo json_encode($processor->getCsvHeaders());
```

## API Reference
See the PHPDoc comments in `array_data1.php` for full method documentation and options.

## License
MIT
