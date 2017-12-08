<?php declare(strict_types=1);

namespace Symplify\Statie\DependencyInjection;

use Nette\Utils\Strings;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symplify\Statie\DependencyInjection\CompilerPass\CollectorCompilerPass;
use Symplify\Statie\Exception\Configuration\DeprecatedConfigSuffixException;

final class AppKernel extends Kernel
{
    /**
     * @var null|string
     */
    private $configFile;

    public function __construct(?string $configConfig = '')
    {
        $this->configFile = $configConfig;

        // random_int is used to prevent container name duplication during tests
        parent::__construct((string) random_int(1, 1000000), false);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../config/config.yml');

        if ($this->configFile) {
            // deprecation info
            if (! Strings::endsWith($this->configFile, 'yml')) {
                throw new DeprecatedConfigSuffixException(sprintf(
                    'Statie now uses "*.yml" files and Symfony DI to configure itself. "%s" given.%sJust rename it to "%s":',
                    $this->configFile,
                    PHP_EOL,
                    pathinfo($this->configFile)['filename'] . '.yml'
                ));
            }

            $loader->load($this->configFile);
        }
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/_statie_kernel';
    }

    protected function build(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new CollectorCompilerPass());
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return [];
    }
}
