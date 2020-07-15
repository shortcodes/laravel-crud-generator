<?php

namespace Shortcodes\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Shortcodes\CrudGenerator\Commands\CodeGeneratorCommand;

class LaravelCrudGeneratorProvider extends ServiceProvider
{
    protected $commands = [
        CodeGeneratorCommand::class
    ];

    public function register()
    {
        $this->commands($this->commands);
    }
}
