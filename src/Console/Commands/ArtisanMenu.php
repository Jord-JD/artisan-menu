<?php

namespace JordJD\ArtisanMenu\Console\Commands;

use JordJD\JsonKeyValueStore\JsonKeyValueStore;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuStyle;
use PhpSchool\CliMenu\Terminal\TerminalFactory;

class ArtisanMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use Artisan via a beautiful console GUI';

    private $app;
    private $commands;
    private $namespaces;

    private $store;

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
        $returnCode = Artisan::call('list', ['--format' => 'json']);

        if ($returnCode != 0) {
            $this->error('Error calling `artisan list` command.');
            return 1;
        }

        $response = json_decode(Artisan::output());

        if (!$response) {
            $this->error('Error decoding `artisan list` response.');
            return 1;
        }

        $this->app = $response->application;
        $this->commands = new Collection($response->commands);
        $this->namespaces = new Collection($response->namespaces);

        $storeFile = 'artisan-menu.json.gz';
        $storePath = storage_path($storeFile);
        $this->store = new JsonKeyValueStore($storePath);

        $gitIgnore = '';
        $gitIgnorePath = storage_path('.gitignore');

        if (file_exists($gitIgnorePath)) {
            $contents = file_get_contents($gitIgnorePath);
            $gitIgnore = $contents === false ? '' : $contents;
        }

        if (strpos($gitIgnore, $storeFile) === false) {
            $gitIgnore = rtrim($gitIgnore).PHP_EOL.$storeFile.PHP_EOL;
            file_put_contents($gitIgnorePath, $gitIgnore);
        }

        $this->mainMenu();

        return 0;
    }

    private function mainMenu()
    {

        $menu = new CliMenuBuilder();
        $menu->setTitle($this->getMenuTitle());
        $menu->setBackgroundColour('black');
        $menu->setForegroundColour('white');

        $menu->addLineBreak();

        $recentCommands = $this->store->get('recent_commands');

        if (is_array($recentCommands)) {
            foreach($recentCommands as $commandName) {
                $this->addCommandMenuItem($menu, $this->getCommandByName($commandName));
            }
        }

        $menu->addLineBreak();

        foreach($this->namespaces as $namespace) {
            $menu->addSubMenuFromBuilder(
                $this->getNamespaceTitle($namespace),
                $this->getNamespaceMenuBuilder($namespace)
            );
        }

        $menu->addLineBreak();

        $menu = $menu->build();

        $menu->open();
    }

    private function getMenuTitle(\stdClass $namespace = null)
    {
        $title = 'Artisan Menu - ';
        if ($namespace) {
            $title .= $this->getNamespaceTitle($namespace).' - ';
        }
        $title .= $this->getAppString();
        return $title;
    }

    private function getNamespaceTitle(\stdClass $namespace)
    {
        return $namespace->id == '_global' ? 'Global' : ucfirst($namespace->id);
    }

    private function getAppString()
    {
        return $this->app->name.' '.$this->app->version;
    }

    private function getNamespaceMenuBuilder(\stdClass $namespace)
    {
        $menu = CliMenuBuilder::newSubMenu(TerminalFactory::fromSystem());
        $menu->setTitle($this->getMenuTitle($namespace));
        $menu->setBackgroundColour('black');
        $menu->setForegroundColour('white');

        $namespaceRootCommand = $this->commands->where('name', $namespace->id)->first();

        if ($namespaceRootCommand) {
            $this->addCommandMenuItem($menu,$namespaceRootCommand);
        }

        foreach($namespace->commands as $commandName) {
            $this->addCommandMenuItem($menu, $this->getCommandByName($commandName));
        }


        $menu->addLineBreak();

        return $menu;
    }

    private function getCommandByName(string $commandName)
    {
        return $this->commands->where('name', $commandName)->first();
    }

    private function addCommandMenuItem(CliMenuBuilder $menu, \stdClass $command = null)
    {
        if (!$command || in_array($command->name, ['list', 'help', 'inspire', 'optimize', 'menu'])) {
            return;
        }

        $menu->addItem(ucfirst($command->description.' ('.$command->name.')'), function($menu) use ($command) {
            $this->commandSelected($menu, $command);
        });
    }

    private function commandSelected(CliMenu $menu, \stdClass $command)
    {
        $menuStyle = new MenuStyle();
        $menuStyle->setBg('white');
        $menuStyle->setFg('black');

        $arguments = $this->collectCommandInput($menu, $menuStyle, $command);

        if ($arguments === null) {
            return;
        }

        $this->rememberCommand($command->name);

        try {
            Artisan::call($command->name, $arguments);
            $output = Artisan::output();
            $success = true;
        } catch (\Exception $e) {
            $output = $e->getMessage();
            $success = false;
        }

        if (!$output) {
            $output = 'This command produced no output.';
        }

        $lines = explode(PHP_EOL, $output);

        $outputMenu = (new CliMenuBuilder);
        $outputMenu->setTitle($command->description.' ('.$command->name.')');
        $outputMenu->setMarginAuto();
        $outputMenu->setBackgroundColour($success ? 'black': 'red');
        $outputMenu->setForegroundColour('white');
        $outputMenu->setExitButtonText('OK');
        foreach($lines as $line) {
            $outputMenu->addStaticItem($line);
        }
        $outputMenu->addLineBreak();
        $outputMenu = $outputMenu->build();
        $outputMenu->open();

        $menu->close();

        $this->mainMenu();
    }

    private function collectCommandInput(CliMenu $menu, MenuStyle $menuStyle, \stdClass $command)
    {
        $arguments = [];

        foreach ($command->definition->arguments as $argument) {
            $required = !empty($argument->is_required);
            $question = $menu->askText($menuStyle)
                ->setPromptText(
                    ($required ? 'Required' : 'Optional').' argument '.$argument->name
                    .($argument->description ? ': '.lcfirst($argument->description) : '')
                );

            if (!$required) {
                $question->setValidator(function () {
                    return true;
                });
            }

            $value = $question->ask()->fetch();

            if ($required && $value === '') {
                return null;
            }

            if ($value !== '') {
                $arguments[$argument->name] = $this->normaliseDefinitionValue($argument, $value);
            }
        }

        foreach ($this->getCommandSpecificOptions($command) as $option) {
            $key = '--'.$option->name;

            if (empty($option->accept_value)) {
                $value = $menu->askText($menuStyle)
                    ->setPromptText('Enable '.$key.'? Enter yes or leave blank')
                    ->setValidationFailedText('Enter yes, no, or leave blank')
                    ->setValidator(function ($input) {
                        return $input === '' || in_array(strtolower($input), ['y', 'yes', 'n', 'no']);
                    })
                    ->ask()
                    ->fetch();

                if ($this->isAffirmative($value)) {
                    $arguments[$key] = true;
                }

                continue;
            }

            $value = $menu->askText($menuStyle)
                ->setPromptText(
                    'Optional '.$key
                    .($option->description ? ': '.lcfirst($option->description) : '')
                )
                ->setValidator(function () {
                    return true;
                })
                ->ask()
                ->fetch();

            if ($value !== '') {
                $arguments[$key] = $this->normaliseDefinitionValue($option, $value);
            }
        }

        return $arguments;
    }

    protected function getCommandSpecificOptions(\stdClass $command)
    {
        $globalOptions = [
            'help',
            'quiet',
            'silent',
            'verbose',
            'version',
            'ansi',
            'no-ansi',
            'no-interaction',
            'env',
        ];

        return array_values(array_filter($command->definition->options, function ($option) use ($globalOptions) {
            return !in_array($option->name, $globalOptions);
        }));
    }

    protected function normaliseDefinitionValue(\stdClass $definition, $value)
    {
        if (!empty($definition->is_array) || !empty($definition->is_multiple)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
        }

        return $value;
    }

    protected function isAffirmative($value)
    {
        return in_array(strtolower(trim($value)), ['y', 'yes']);
    }

    private function rememberCommand($commandName)
    {
        $recentCommands = $this->store->get('recent_commands');

        if (!is_array($recentCommands)) {
            $recentCommands = [];
        }

        $key = array_search($commandName, $recentCommands);

        if ($key !== false) {
            unset($recentCommands[$key]);
        }

        array_unshift($recentCommands, $commandName);

        while (count($recentCommands) > 5) {
            array_pop($recentCommands);
        }

        $this->store->set('recent_commands', $recentCommands);
    }
}
