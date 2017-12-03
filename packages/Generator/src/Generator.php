<?php declare(strict_types=1);

namespace Symplify\Statie\Generator;

use Symplify\Statie\FileSystem\FileFinder;
use Symplify\Statie\Generator\Configuration\GeneratorConfiguration;
use Symplify\Statie\Generator\Configuration\GeneratorElement;

final class Generator
{
    /**
     * @var GeneratorConfiguration
     */
    private $generatorConfiguration;

    /**
     * @var FileFinder
     */
    private $fileFinder;

    public function __construct(GeneratorConfiguration $generatorConfiguration, FileFinder $fileFinder)
    {
        $this->generatorConfiguration = $generatorConfiguration;
        $this->fileFinder = $fileFinder;
    }

    public function run(): void
    {
        foreach ($this->generatorConfiguration->getGeneratorElements() as $generatorElement) {
            $this->processGeneratorElement($generatorElement);
        }
    }

    private function processGeneratorElement(GeneratorElement $generatorElement): void
    {
        $items = $this->fileFinder->findInDirectory($generatorElement->getPath());

        dump($items);
        dump($generatorElement);
        die;

        // find files in...
        // process object
        // save them to property
        // render them
    }
}