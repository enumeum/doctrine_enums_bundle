<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/*
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Alexey Sitka <alexey.sitka@gmail.com>
 */

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', sys_get_temp_dir() . '/doctrine-enums-bundle-tests');
define('VENDOR_PATH', realpath(dirname(__DIR__) . '/vendor'));

$loader = require dirname(__DIR__) . '/vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$reader = new AnnotationReader();
$reader = new PsrCachedReader($reader, new ArrayAdapter());
$_ENV['annotation_reader'] = $reader;
