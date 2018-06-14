<?php

namespace EliPett\CodeGeneration\Console;

use EliPett\CaseConverter\Enums\CaseConversion;
use EliPett\CodeGeneration\Structs\Generator;
use EliPett\CodeGeneration\Structs\Stub;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class RunGenerators extends Command
{
    protected $signature = 'generate {generators}';

    private $generatorFactory;
    private $parameters;

    public function handle()
    {
        try {

            $this->setConsoleStyles();
            $this->loadGeneratorFactory();

            $generators = explode(',', $this->argument('generators'));

            foreach ($generators as $generator) {
                if (!$generator = $this->getGenerator($generator)) {
                    continue;
                }

                $this->runGenerator($generator);
            }

        } catch (\Exception $exception) {
            $this->message($exception->getMessage(), 'error');
        }
    }

    private function setConsoleStyles(): void
    {
        $this->output->getFormatter()->setStyle('error', new OutputFormatterStyle('red'));
        $this->output->getFormatter()->setStyle('highlight', new OutputFormatterStyle('blue'));
    }

    private function loadGeneratorFactory(): void
    {
        $this->generatorFactory = $this->config('factories.generator');

        if (!class_exists($this->generatorFactory)) {
            throw new \InvalidArgumentException($this->trans('generator-factory-not-found', [
                'path' => $this->generatorFactory
            ]));
        }
    }

    private function message(string $string, string $style = 'info'): void
    {
        $this->output->writeln("<$style>$string</$style>");
    }

    private function config(string $key, $default = null): string
    {
        return config("codegeneration.$key", $default);
    }

    private function trans(string $key, array $parameters = []): string
    {
        return trans("codegeneration::messages.$key", $parameters);
    }

    private function getGenerator(string $name): ?Generator
    {
        $method = lower_camel_case($this->config("generator-aliases.$name", $name));

        if (!method_exists($this->generatorFactory, $method)) {
            $this->message($this->trans('generator-not-found', [
                'method' => $method
            ]), 'error');

            return null;
        }

        return \call_user_func($this->generatorFactory . "::$method");
    }

    private function runGenerator(Generator $generator): void
    {
        $this->message($this->trans('starting-generator', [
            'name' => $generator->getName()
        ]));

        $parameters = [];
        $stubs = $generator->getStubs();

        foreach ($stubs as $stub) {
            $parameters = array_merge($parameters, $this->scanForParameters($stub->getTargetPath()));
            $parameters = array_merge($parameters, $this->scanForParameters($this->getStubContents($stub)));
        }

        $this->loadParameters(array_unique($parameters));

        foreach ($stubs as $stub) {
            $this->generateStub($stub);
        }
    }

    private function generateStub(Stub $stub)
    {
        $targetPath = $this->filterContents($stub->getTargetPath());
        $targetDirectory = $this->filterContents($stub->getTargetDirectory());

        $contents = $this->filterContents($this->getStubContents($stub));

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        file_put_contents($targetPath, $contents);

        $this->message($this->trans('stub-generated', [
            'path' => $targetPath
        ]), 'highlight');
    }

    private function scanForParameters($contents): array
    {
        $parameters = [];

        $conversions = [
            'NO_CASE',
            CaseConversion::LOWER_CASE,
            CaseConversion::UPPER_CASE,
            CaseConversion::TIGHT_CASE,
            CaseConversion::LOWER_SNAKE_CASE,
            CaseConversion::UPPER_SNAKE_CASE,
            CaseConversion::LOWER_CAMEL_CASE,
            CaseConversion::UPPER_CAMEL_CASE,
            CaseConversion::LOWER_HYPHEN_CASE,
            CaseConversion::UPPER_HYPHEN_CASE
        ];

        foreach ($conversions as $conversion) {
            $parameters = array_merge($parameters, $this->scanForParameter($contents, $conversion));
        }

        return array_unique($parameters);
    }

    private function scanForParameter($contents, $conversion): array
    {
        preg_match_all('/\$([A-Z,_]+)_' . strtoupper($conversion) . '\$/', $contents, $matches);

        return $matches[1];
    }

    private function loadParameter($parameter): void
    {
        if (!$value = $this->ask('Enter ' . upper_case($parameter))) {
            while ($value === null) {
                $this->message($this->trans('parameter-is-required'), 'error');
                $value = $this->ask('Enter ' . upper_case($parameter));
            }
        }

        $parameter = lower_snake_case($parameter);
        $this->parameters[$parameter] = $value;
    }

    private function loadParameters(array $parameters): void
    {
        if (empty($parameters)) {
            throw new \InvalidArgumentException($this->trans('no-parameters-found'));
        }

        foreach ($parameters as $parameter) {
            $this->loadParameter($parameter);
        }
    }

    private function getStubContents(Stub $stub)
    {
        $path = $this->config('stub-directory', __DIR__ . '/../Resources/stubs') . '/' . $stub->getStubPath();

        if (!file_exists($path)) {
            throw new \InvalidArgumentException($this->trans('stub-not-found', [
                'path' => $path
            ]));
        }

        return file_get_contents($path);
    }

    private function filterContents($contents)
    {
        foreach($this->parameters as $key => $value) {
            $contents = str_replace(
                [
                    '$' . strtoupper($key) . '_' . strtoupper('NO_CASE') . '$',

                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::LOWER_CASE) . '$',
                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::UPPER_CASE) . '$',

                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::TIGHT_CASE) . '$',

                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::LOWER_SNAKE_CASE) . '$',
                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::UPPER_SNAKE_CASE) . '$',

                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::LOWER_CAMEL_CASE) . '$',
                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::UPPER_CAMEL_CASE) . '$',

                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::LOWER_HYPHEN_CASE) . '$',
                    '$' . strtoupper($key) . '_' . strtoupper(CaseConversion::UPPER_HYPHEN_CASE) . '$'
                ],
                [
                    $value,

                    lower_case($value),
                    upper_case($value),

                    tight_case($value),

                    lower_snake_case($value),
                    upper_snake_case($value),

                    lower_camel_case($value),
                    upper_camel_case($value),

                    lower_hyphen_case($value),
                    upper_hyphen_case($value),
                ],
                $contents
            );
        }

        return $contents;
    }
}
