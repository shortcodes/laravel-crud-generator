<?php

namespace Shortcodes\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CodeGeneratorCommand extends Command
{
    protected $signature = 'make:crud';

    protected $description = 'Make all nessesary things for controller';
    private string $model;
    private $tdb = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->model = Str::lower(Str::singular($this->ask('What model do you want to create?')));

        $this->info('Creating migration and model ...');
        Artisan::call('make:model ' . Str::ucfirst(Str::camel($this->model)) . ' -m');
        $this->tbd[] = ['edit migration', '*_create_' . Str::lower(Str::plural($this->model)) . '_table.php'];
        $this->tbd[] = ['edit model', 'App/' . Str::ucfirst(Str::camel($this->model)) . '.php'];
        $this->info('Migration created' . "\n");

        $this->info('Creating controller ...');
        $this->createControllerFromStub();
        $this->info('Controller created' . "\n");

        if ($this->confirm('Do you wish to custom requests?', true)) {
            $this->info('Creating requests ...');
            $this->createRequestsFromStub();
            $this->info('Requests created' . "\n");
        }

        if ($this->confirm('Do you wish to custom resource?', true)) {
            $this->info('Creating resource ...');
            $this->createResourceFromStub();
            $this->info('Resource created' . "\n");
        }

        if ($this->confirm('Do you wish to create factory?', true)) {
            $this->info('Creating factory ...');
            $this->createFactory();
            $this->info('Factory created' . "\n");
        }

        if ($this->confirm('Do you wish to create tests?', true)) {
            $this->info('Creating tests ...');
            $this->createTestsFromStub();
            $this->info('Tests created' . "\n");
        }

        $this->info('Creating swagger documentation ...');
        $this->createSwagger();
        $this->info('Swagger documentation created' . "\n");

        $this->tbd[] = ['add entry to route file (api.php)', "Route::apiResource('" . Str::kebab(Str::plural($this->model)) . "','" . Str::ucfirst(Str::camel($this->model)) . "Controller');"];
        $this->table(['Things to be done', 'Files'], $this->tbd);

    }


    private function createControllerFromStub()
    {
        $client = Storage::createLocalDriver(['root' => app_path() . '/Http/Controllers/']);

        if ($client->exists(Str::ucfirst(Str::camel($this->model)) . 'Controller.php')) {
            $this->error('File ' . app_path() . '/Http/Controllers/' . Str::ucfirst(Str::camel($this->model)) . 'Controller.php' . ' already exists (skipped)');
            return;
        }

        $client->put(Str::ucfirst(Str::camel($this->model)) . 'Controller.php', $this->controllerStub($this->model));
        $this->tbd[] = ['edit controller (optional)', 'App/Http/Controllers/' . Str::ucfirst(Str::camel($this->model)) . 'Controller.php'];
    }

    private function createRequestsFromStub()
    {
        $client = Storage::createLocalDriver(['root' => app_path() . '/Http/Requests/' . Str::plural(Str::ucfirst(Str::camel($this->model)))]);

        collect(['Store', 'Update', 'Delete', 'Index', 'Show'])->each(function ($fileName) use ($client) {

            if ($client->exists($fileName . Str::ucfirst(Str::camel($this->model)) . 'Request.php')) {
                $this->error('File ' . app_path() . '/Http/Requests/' . Str::plural(Str::ucfirst(Str::camel($this->model))) . $fileName . 'Request.php already exists (skipped)');
                return;
            }

            $client->put($fileName . Str::ucfirst(Str::camel($this->model)) . 'Request.php', $this->requestStub($fileName, $this->model));
        });

        $this->tbd[] = ['edit requests', 'App/Http/Requests/' . Str::plural(Str::ucfirst(Str::camel($this->model))) . '/*' . Str::ucfirst(Str::camel($this->model)) . 'Request.php'];

    }

    private function createResourceFromStub()
    {
        $client = Storage::createLocalDriver(['root' => app_path() . '/Http/Resources/']);

        if ($client->exists(Str::ucfirst(Str::camel($this->model)) . 'Resource.php')) {
            $this->error('File ' . app_path() . '/Http/Resources/' . Str::ucfirst(Str::camel($this->model)) . 'Resource.php' . ' already exists (skipped)');
            return;
        }

        Artisan::call('make:resource ' . Str::ucfirst(Str::camel($this->model)) . 'Resource');

        $this->tbd[] = ['edit resource (optional)', 'App/Http/Resources/' . Str::ucfirst(Str::camel($this->model)) . '/' . Str::ucfirst(Str::camel($this->model)) . 'Resource.php'];
    }

    private function createFactory()
    {
        Artisan::call('make:factory ' . Str::ucfirst(Str::camel($this->model)) . 'Factory  --model=' . Str::ucfirst(Str::camel($this->model)));

        $this->tbd[] = ['edit factory', 'database/factories/' . Str::ucfirst(Str::camel($this->model)) . 'Factory.php'];

    }

    private function createSwagger()
    {
        Artisan::call('make:annotation ' . Str::ucfirst(Str::camel($this->model)));
        $this->tbd[] = ['edit swagger model', 'App/Swagger/Models/' . Str::ucfirst(Str::camel($this->model)) . '.php'];

    }

    private function createTestsFromStub()
    {
        $client = Storage::createLocalDriver(['root' => base_path() . '/tests/Feature/' . Str::ucfirst(Str::camel($this->model)) . '/Requests']);

        collect(['Store', 'Update', 'Delete'])->each(function ($fileName) use ($client) {

            if ($client->exists($fileName . Str::ucfirst(Str::camel($this->model)) . 'Test.php')) {
                $this->error('File ' . base_path() . '/tests/Feature/' . Str::ucfirst(Str::camel($this->model)) . '/Requests/' . Str::plural(Str::ucfirst(Str::camel($this->model))) . $fileName . 'Test.php already exists (skipped)');
                return;
            }

            $client->put($fileName . Str::ucfirst(Str::camel($this->model)) . 'Request.php', $this->requestTestStub($fileName, $this->model));
        });

        $client = Storage::createLocalDriver(['root' => base_path() . '/tests/Feature/' . Str::ucfirst(Str::camel($this->model))]);

        if ($client->exists('Crud' . Str::ucfirst(Str::camel($this->model)) . 'Test.php')) {
            $this->error('File ' . base_path() . '/tests/Feature/' . Str::ucfirst(Str::camel($this->model)) . '/Crud' . Str::ucfirst(Str::camel($this->model)) . 'Test.php' . ' already exists (skipped)');
            return;
        }

        $client->put('Crud' . Str::ucfirst(Str::camel($this->model)) . 'Test.php', $this->crudTestStub($this->model));

        $this->tbd[] = ['edit request tests', 'tests/Feature/' . Str::ucfirst(Str::camel($this->model)) . '/Requests/*' . Str::ucfirst(Str::camel($this->model)) . 'RequestTest.php'];

    }

    private function controllerStub(string $model)
    {
        $studlyModel = Str::ucfirst(Str::camel($model));
        return <<<EOT
<?php

namespace App\Http\Controllers;

use App\\{$studlyModel};
use Shortcodes\AbstractResourceController\Controllers\AbstractResourceController;

class {$studlyModel}Controller extends AbstractResourceController
{
    protected \$model = {$studlyModel}::class;

    public function access()
    {
        \$this->middleware('auth:sanctum');
    }
}

EOT;
    }

    private function requestStub(string $fileName, string $model)
    {
        $studlyModel = Str::ucfirst(Str::camel($model));
        $studlyPluralModel = Str::plural($studlyModel);

        $extends = 'FormRequest';
        $use = '
use Illuminate\Foundation\Http\FormRequest;
        ';
        $rules = <<<EOT

    public function rules()
    {
        return [
           //
        ];
    }
EOT;

        if ($fileName === 'Update') {
            $use = '';
            $extends = 'Store' . $studlyModel . 'Request';
            $rules = '';
        }

        return <<<EOT
<?php

namespace App\Http\Requests\\{$studlyPluralModel};
{$use}
class {$fileName}{$studlyModel}Request extends {$extends}
{
   {$rules}
}

EOT;
    }

    private function requestTestStub(string $fileName, string $model)
    {
        $studlyModel = Str::ucfirst(Str::camel($model));
        $studlyPluralModel = Str::plural($studlyModel);

        return <<<EOT
<?php

namespace Tests\Feature\\{$studlyModel}\Requests;

use App\\{$studlyModel};
use App\Http\Requests\\{$studlyPluralModel}\\{$fileName}{$studlyModel}Request;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Shortcodes\Tests\Blueprints\FormRequestTest;

class {$fileName}{$studlyModel}RequestTest extends FormRequestTest
{
    use DatabaseTransactions;

    protected \$model = {$fileName}{$studlyModel}Request::class;

    /**
     * @test
     */
    public function i_can_do()
    {
        \$this->prepareRequest([
           //
        ])->assertValidRequest();
    }
}

EOT;
    }

    private function crudTestStub(string $model)
    {
        $studlyModel = Str::ucfirst(Str::camel($model));
        return <<<EOT
<?php

namespace Tests\Feature\\{$studlyModel};

use App\\{$studlyModel};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Shortcodes\Tests\Blueprints\ApiCrudTest;

class Crud{$studlyModel}Test extends ApiCrudTest
{
    use DatabaseTransactions;

    protected \$model = {$studlyModel}::class;
}

EOT;
    }
}
