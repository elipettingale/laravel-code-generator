<?php

namespace EliPett\CodeGeneration;

use EliPett\CodeGeneration\Console\Commands\GenerateController;
use EliPett\CodeGeneration\Console\Commands\GenerateRepository;
use EliPett\CodeGeneration\Console\Commands\GenerateEntity;
use EliPett\CodeGeneration\Console\Commands\GenerateProvider;
use EliPett\CodeGeneration\Console\Commands\GenerateView;
use EliPett\CodeGeneration\Console\RunGenerators;
use Illuminate\Support\ServiceProvider;

class CodeGenerationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadCommands();
        $this->loadTranslations();
        $this->publishAssets();
    }

    private function loadCommands()
    {
        $this->commands([
            RunGenerators::class
        ]);
    }

    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'codegeneration');
    }

    private function publishAssets()
    {
        $this->publishes([
            __DIR__ . '/../config/codegeneration.php' => config_path('codegeneration.php'),
        ], 'config');
    }
}
