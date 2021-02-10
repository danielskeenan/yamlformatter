<?php
// Autoloader
foreach ([__DIR__.'/../../../autoload.php', __DIR__.'/../vendor/autoload.php'] as $maybeAutoloader) {
    if (file_exists($maybeAutoloader)) {
        require_once $maybeAutoloader;
        break;
    }
}


use Symfony\Component\Console\Application;
use DragoonBoots\YamlFormatter\Command;

$application = new Application();
$application->addCommands(
    [
        new Command\Format(),
    ]
);
$application->run();
