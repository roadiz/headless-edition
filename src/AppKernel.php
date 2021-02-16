<?php
declare(strict_types=1);

namespace App;

use RZ\Roadiz\Core\Kernel;

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
    public function getPublicFilesPath()
    {
        return $this->getPublicDir() . $this->getPublicFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateFilesPath()
    {
        return $this->getProjectDir() . $this->getPrivateFilesBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getFontsFilesPath()
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
        $container->register(new \Themes\AbstractApiTheme\Services\AbstractApiServiceProvider());
        $container->register(new \App\AppServiceProvider());

        /*
         * Add your own service providers.
         */
    }
}
