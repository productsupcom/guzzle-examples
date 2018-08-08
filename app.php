#!/usr/bin/env php
<?php

require __DIR__ . '/bootstrap.php';

use Productsup\Command\ImportCommand;
use Productsup\Command\ImportCoroutineCommand;
use Symfony\Component\Console\Application;

$console = new Application('bin-guzzle-example');

$console->add(new ImportCommand());
$console->add(new ImportCoroutineCommand());

$console->run();
