<?php
require_once 'ArrayDataProcessor.php';

// --- Example: Expanding by nested list (accents) ---

// Example data with nested accents
$accentData = [
    [
        'id' => 1,
        'name' => 'Voice 1',
        'accents' => [
            ['id' => 101, 'accent_name' => 'British', 'preview_url' => 'url1'],
            ['id' => 102, 'accent_name' => 'American', 'preview_url' => 'url2'],
        ],
        'category' => 'A',
    ],
    [
        'id' => 2,
        'name' => 'Voice 2',
        'accents' => [
            ['id' => 201, 'accent_name' => 'Australian', 'preview_url' => 'url3'],
        ],
        'category' => 'B',
    ],
    [
        'id' => 3,
        'name' => 'Voice 3',
        'accents' => [], // No accents
        'category' => 'C',
    ],
];

$accentProcessor = new ArrayDataProcessor($accentData);
$accentProcessor->expandByNestedList('accents', [
    'accent_id' => 'id',
    'accent_name' => 'accent_name',
    'preview_url' => 'preview_url',
]);
$accentProcessor->setFields(['id', 'name', 'category', 'accent_id', 'accent_name', 'preview_url']);

// Output the expanded, flattened array

echo "\nExpanded by accents (flattened):\n";
print_r($accentProcessor->toArray());
// Output as JSON

echo "\nExpanded JSON:\n";
echo $accentProcessor->toJson(JSON_PRETTY_PRINT) . "\n";




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
    [
        'id' => '2',
        'title' => 'Chill Stream',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '800',
        'created_at' => '2025-05-10 10:30:00',
        'tags' => ['relax', 'music'],
        'status' => '0',
        'rating' => '3.8',
        'details' => json_encode([
            'platform' => 'YouTube',
            'quality' => 'SD',
            'meta' => ['lang' => 'fr']
        ]),
        'metadata' => json_encode(['foo' => 'baz']),
        'viewer_count' => '500',
    ],
    [
        'id' => '3',
        'title' => 'Cooking Show',
        'category' => 'Lifestyle',
        'is_featured' => 'N',
        'views' => '1500',
        'created_at' => '2025-05-12 14:00:00',
        'tags' => '',
        'status' => '2',
        'rating' => '4.9',
        'details' => json_encode([
            'platform' => 'Facebook',
            'quality' => 'HD',
            'meta' => ['lang' => 'es']
        ]),
        'metadata' => json_encode(['foo' => 'qux']),
        'viewer_count' => '2000',
    ],
    [
        'id' => '4',
        'title' => 'Tech Review',
        'category' => 'Technology',
        'is_featured' => 'Y',
        'views' => '2000',
        'created_at' => '2025-05-15 09:00:00',
        'tags' => null,
        'status' => '3',
        'rating' => '4.2',
        'details' => json_encode([
            'platform' => 'Twitch',
            'quality' => '4K',
            'meta' => ['lang' => 'en']
        ]),
        'metadata' => json_encode(['foo' => 'zap']),
        'viewer_count' => '1500',
    ],
];

// 1. Create the processor
$processor = new ArrayDataProcessor($data);

// 2. Configure field types and aliases (including closures and edge cases)
$processor
    ->setFieldType('id', 'int')
    ->setFieldType('views', 'int')
    ->setFieldType('created_at', function ($v) {
        return (new DateTime($v))->format('Y-m-d H:i');
    })
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
    ->setFieldType('rating', function ($v) {
        return number_format((float)$v, 2, '.', '');
    })
    ->setFieldType('metadata', function ($v) {
        $meta = json_decode($v, true);
        $meta['processed_at'] = date('Y-m-d H:i:s');
        return $meta;
    })
    ->setFieldType('viewer_count', function ($v) {
        return (int)$v;
    })
    ->setFieldAlias('views', 'view_count')
    ->setFieldAlias('created_at', 'published_at')
    ->setFieldAlias('details.platform', 'platform')
    ->setFieldAlias('viewer_count', 'audience');

// 3. Select only specific fields for output (including nested/dot notation and aliases)
$processor->setFields([
    'id',
    'title',
    'view_count',
    'category',
    'published_at',
    'platform',
    'tags',
    'status',
    'rating',
    'audience',
    'metadata',
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

// --- End of Comprehensive Example Usage ---
