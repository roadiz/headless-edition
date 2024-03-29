<?php
declare(strict_types=1);

namespace App;

use RZ\Roadiz\Core\Kernel;
use Themes\AbstractApiTheme\Services\AbstractApiServiceProvider;
use Themes\Rozier\Services\RozierServiceProvider;

/**
 * Customize Roadiz kernel with your own project settings.
 */
class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $this->rootDir = $this->getProjectDir() . '/app';
        }
        return $this->rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicDir()
    {
        return $this->getProjectDir() . '/web';
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicFilesPath(): string
    {
        return $this->getPublicDir() . $this->getPublicFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateFilesPath(): string
    {
        return $this->getProjectDir() . $this->getPrivateFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getFontsFilesPath(): string
    {
        return $this->getProjectDir() . $this->getFontsFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $container): void
    {
        parent::register($container);
        // Headless edition: do not remove API services
        $container->register(new RozierServiceProvider());
        $container->register(new AbstractApiServiceProvider());
        $container->register(new AppServiceProvider());

        /*
         * Add your own service providers.
         */
    }
}
