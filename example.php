<?php
require_once 'array_data1.php';

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
