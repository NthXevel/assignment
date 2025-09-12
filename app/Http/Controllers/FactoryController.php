<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FactoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_products');
    }

    /**
     * Show all existing product factories.
     */
    public function index()
    {
        $factories = $this->getFactories();
        return view('factories.index', compact('factories'));
    }

    /**
     * Show form to create a new factory.
     */
    public function create()
    {
        return view('factories.create');
    }

    /**
     * Edit an existing factory class file.
     */
    public function edit(string $factoryName)
    {
        $path = app_path("Factories/Products/{$factoryName}.php");
        if (!File::exists($path)) {
            return redirect()->route('factories.index')->withErrors(['error' => 'Factory file not found.']);
        }

        $content = File::get($path);
        [$description, $specs] = $this->extractFactoryMeta($content);

        return view('factories.edit', [
            'factoryName' => $factoryName,
            'description' => $description,
            'specifications' => $specs,
        ]);
    }

    /**
     * Update an existing factory class file.
     */
    public function update(Request $request, string $factoryName)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'specifications.*.key' => 'required_with:specifications|string',
            'specifications.*.value' => 'required_with:specifications',
            'specifications.*.type' => 'required_with:specifications|in:string,integer,boolean,float',
        ]);

        $path = app_path("Factories/Products/{$factoryName}.php");
        if (!File::exists($path)) {
            return redirect()->route('factories.index')->withErrors(['error' => 'Factory file not found.']);
        }

        // Build specs array
        $specs = [];
        if (!empty($validated['specifications'])) {
            foreach ($validated['specifications'] as $spec) {
                $specs[$spec['key']] = $this->castValue($spec['value'], $spec['type']);
            }
        }

        $className = $factoryName; // already ends with Factory
        $content = $this->generateClassContent($className, $specs, $validated['description'] ?? '');
        File::put($path, $content);

        return redirect()->route('factories.index')->with('success', 'Factory updated successfully!');
    }

    /**
     * Store a new factory class file.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'specifications.*.key' => 'required_with:specifications|string',
            'specifications.*.value' => 'required_with:specifications',
            'specifications.*.type' => 'required_with:specifications|in:string,integer,boolean,float',
        ]);

        try {
            $this->makeFactoryFile($validated);

            return redirect()
                ->route('factories.index')
                ->with('success', 'Factory created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create factory: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a factory file.
     */
    public function destroy(string $factoryName)
    {
        $path = app_path("Factories/Products/{$factoryName}.php");

        if (File::exists($path)) {
            File::delete($path);

            return redirect()
                ->route('factories.index')
                ->with('success', 'Factory deleted successfully!');
        }

        return redirect()
            ->route('factories.index')
            ->withErrors(['error' => 'Factory file not found.']);
    }

    /* -----------------------------------------------------------------
     |  Private Helpers
     | -----------------------------------------------------------------
     */

    /**
     * Build the PHP factory class file.
     */
    private function makeFactoryFile(array $data): void
    {
        $className = Str::studly($data['factory_name']) . 'Factory';
        $filePath = app_path("Factories/Products/{$className}.php");
        $description = $data['description'] ?? '';

        // Ensure directory exists
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        // Build specifications array
        $specs = [];
        if (!empty($data['specifications'])) {
            foreach ($data['specifications'] as $spec) {
                $specs[$spec['key']] = $this->castValue($spec['value'], $spec['type']);
            }
        }

        $content = $this->generateClassContent($className, $specs, $description);

        File::put($filePath, $content);
    }

    /**
     * Cast spec value to proper type.
     */
    private function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float' => (float) $value,
            default => (string) $value,
        };
    }

    /**
     * Generate the PHP code for a factory class.
     */
    private function generateClassContent(string $className, array $specs, string $description): string
    {
        $specArray = var_export($specs, true);

        return <<<PHP
<?php

namespace App\Factories\Products;

use App\Models\Product;

/**
 * {$description}
 */
class {$className} extends ProductFactory
{
    public function createProduct(array \$data): Product
    {
        \$data['specifications'] = array_merge(\$data['specifications'] ?? [], {$specArray});

        return Product::create(\$data);
    }
}
PHP;
    }

    /**
     * Extract description and specifications from existing factory PHP content.
     */
    private function extractFactoryMeta(string $content): array
    {
        $description = '';
        $specs = [];

        // Extract description in the class doc comment
        if (preg_match('#/\*\*\s*\*\s*(.*?)\s*\*/#s', $content, $m)) {
            $description = trim(preg_replace('/^\s*\*\s?/m', '', $m[1]));
        }

        // Extract array in array_merge($data['specifications'] ?? [], [ ... ]);
        if (preg_match('#array_merge\(\$data\[\'specifications\'\]\s*\?\?\s*\[\],\s*(\[[\s\S]*?\])\)#', $content, $m)) {
            $arrayCode = $m[1];
            // Safely eval as PHP array
            try {
                $specsArray = eval('return ' . $arrayCode . ';');
                if (is_array($specsArray)) {
                    foreach ($specsArray as $k => $v) {
                        $type = gettype($v);
                        $mapped = match ($type) {
                            'integer' => 'integer',
                            'double' => 'float',
                            'boolean' => 'boolean',
                            default => 'string',
                        };
                        $specs[] = ['key' => (string) $k, 'value' => (string) $v, 'type' => $mapped];
                    }
                }
            } catch (\Throwable $e) {
                // ignore parse errors; return empty specs
            }
        }

        return [$description, $specs];
    }

    /**
     * Get a list of all existing factories.
     */
    private function getFactories()
    {
        $path = app_path('Factories/Products');
        $factories = [];

        if (\Illuminate\Support\Facades\File::exists($path)) {
            foreach (\Illuminate\Support\Facades\File::files($path) as $file) {
                if ($file->getExtension() === 'php' && $file->getBasename('.php') !== 'ProductFactory') {
                    $factories[] = [
                        'name' => $file->getBasename('.php'),
                        'path' => $file->getPathname(),
                        'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
        }

        return collect($factories);
    }
}
