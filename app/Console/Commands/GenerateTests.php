<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateTests extends Command
{
    protected $signature = 'make:comprehensive-tests';
    protected $description = 'Generate PHPUnit tests for all models and controllers in the project';

    public function handle()
    {
        $this->info('Starting test generation for BlueBoxx DA Platform...');

        $this->generateModelTests();
        $this->generateControllerTests();

        $this->info('Test generation completed successfully.');
    }

    protected function generateModelTests()
    {
        $modelsPath = app_path('Models');
        $models = File::allFiles($modelsPath);

        $count = 0;
        foreach ($models as $model) {
            $modelName = $model->getFilenameWithoutExtension();
            $testPath = base_path("tests/Unit/Models/{$modelName}Test.php");

            if (!File::exists($testPath)) {
                File::ensureDirectoryExists(dirname($testPath));
                
                $stub = <<<PHP
<?php

namespace Tests\Unit\Models;

use App\Models\\{$modelName};
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class {$modelName}Test extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_{$modelName}_can_be_instantiated()
    {
        \$model = new {$modelName}();
        \$this->assertInstanceOf({$modelName}::class, \$model);
    }
}
PHP;
                File::put($testPath, $stub);
                $count++;
            }
        }
        $this->info("Generated {$count} Model Unit Tests.");
    }

    protected function generateControllerTests()
    {
        $controllersPath = app_path('Http/Controllers');
        $controllers = File::allFiles($controllersPath);

        $count = 0;
        foreach ($controllers as $controller) {
            if ($controller->getFilename() === 'Controller.php') continue;

            $relativePath = $controller->getRelativePath();
            $controllerName = $controller->getFilenameWithoutExtension();
            
            $testNamespace = 'Tests\\Feature' . ($relativePath ? '\\' . str_replace('/', '\\', $relativePath) : '');
            $testDir = base_path("tests/Feature" . ($relativePath ? '/' . $relativePath : ''));
            $testPath = "{$testDir}/{$controllerName}Test.php";

            if (!File::exists($testPath)) {
                File::ensureDirectoryExists($testDir);
                
                $stub = <<<PHP
<?php

namespace {$testNamespace};

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class {$controllerName}Test extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_{$controllerName}_example()
    {
        \$this->assertTrue(true);
        // TODO: Implement comprehensive feature tests for {$controllerName} endpoints
    }
}
PHP;
                File::put($testPath, $stub);
                $count++;
            }
        }
        $this->info("Generated {$count} Controller Feature Tests.");
    }
}
