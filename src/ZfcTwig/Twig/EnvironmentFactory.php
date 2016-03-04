<?php

namespace ZfcTwig\Twig;

use RuntimeException;
use Twig_Environment;
use Twig_LoaderInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EnvironmentFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Twig_Environment
     * @throws \RuntimeException
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var \ZfcTwig\moduleOptions $options */
        $options  = $serviceLocator->get('ZfcTwig\ModuleOptions');
        $envClass = $options->getEnvironmentClass();

        if (!$serviceLocator->has($options->getEnvironmentLoader())) {
            throw new RuntimeException(
                sprintf(
                    'Loader with alias "%s" could not be found!',
                    $options->getEnvironmentLoader()
                )
            );
        }

        /** @var \Twig_LoaderInterface $loader */
        $loader = $serviceLocator->get($options->getEnvironmentLoader());
        /** @var \Twig_Environment $env */
        $env = new $envClass($loader, $options->getEnvironmentOptions());

        if ($options->getEnableFallbackFunctions()) {
            $helperPluginManager = $serviceLocator->get('ViewHelperManager');
            $env->registerUndefinedFunctionCallback(
                function ($name) use ($helperPluginManager) {
                    if ($helperPluginManager->has($name)) {
                        return new FallbackFunction($name);
                    }
                    return false;
                }
            );
        }

        foreach ($options->getGlobals() as $name => $value) {
            $env->addGlobal($name, $value);
        }

        // Extensions are loaded later to avoid circular dependencies (for example, if an extension needs Renderer).
        return $env;
    }
}
