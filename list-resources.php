<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$resourcesPath = app_path('Filament/Resources');
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesPath)
);

$resources = [];

foreach ($files as $file) {
    if (
        $file->isFile() &&
        str_ends_with($file->getFilename(), 'Resource.php') &&
        !str_contains($file->getFilename(), 'Page') &&
        !str_contains($file->getFilename(), 'RelationManager')
    ) {
        $relativePath = str_replace($resourcesPath . '/', '', $file->getPathname());
        $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
        $fullClassName = "App\\Filament\\Resources\\{$className}";

        if (class_exists($fullClassName)) {
            $reflection = new ReflectionClass($fullClassName);
            $modelProperty = $reflection->getStaticPropertyValue('model', null);

            $resources[] = [
                'resource' => $className,
                'model' => $modelProperty ? class_basename($modelProperty) : 'N/A',
                'full_model' => $modelProperty ?? 'N/A',
            ];
        }
    }
}

echo "📋 RESOURCES FILAMENT TROUVÉES :\n\n";
echo str_pad('RESOURCE', 50) . " | " . str_pad('MODEL', 30) . "\n";
echo str_repeat('-', 85) . "\n";

foreach ($resources as $resource) {
    echo str_pad($resource['resource'], 50) . " | " . str_pad($resource['model'], 30) . "\n";
}

echo "\n✅ Total : " . count($resources) . " resources\n";
