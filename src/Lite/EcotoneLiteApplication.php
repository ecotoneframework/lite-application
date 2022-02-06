<?php declare(strict_types=1);

namespace Ecotone\Lite;

use DI\Container;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\InMemoryConfigurationVariableService;

class EcotoneLiteApplication
{
    public static function boostrap(array $objectsToRegister = [], array $configurationVariables = [], ?ServiceConfiguration $configuration = null, bool $cacheConfiguration = false, ?string $pathToRootCatalog = null): ConfiguredMessagingSystem
    {
        if (!$configuration) {
            $configuration = ServiceConfiguration::createWithDefaults();
        }

        if ($configuration->isLoadingCatalogEnabled() && !$configuration->getLoadedCatalog()) {
            $configuration = $configuration
                                ->withLoadCatalog("src");
        }

//        moving out of vendor catalog
        $rootCatalog = $pathToRootCatalog ?: __DIR__ . "/../../../../../";

        $container = new class($configuration, $cacheConfiguration, $configurationVariables) implements GatewayAwareContainer {
            private Container $container;

            public function __construct(ServiceConfiguration $serviceConfiguration, bool $cacheConfiguration, array $configurationVariables)
            {
                $builder = new \DI\ContainerBuilder();

                if ($cacheConfiguration) {
                    $cacheDirectoryPath = $serviceConfiguration->getCacheDirectoryPath() ?? sys_get_temp_dir();
                    $builder = $builder
                            ->enableCompilation($cacheDirectoryPath . '/ecotone')
                            ->writeProxiesToFile(true, __DIR__ . '/ecotone/proxies')
                            ->ignorePhpDocErrors(true);
                }

                $this->container = $builder->build();
                $this->container->set(ConfigurationVariableService::REFERENCE_NAME, InMemoryConfigurationVariableService::create($configurationVariables));
            }

            public function get($id)
            {
                return $this->container->get($id);
            }

            public function has($id)
            {
                return $this->container->has($id);
            }

            public function set(string $id, object $service)
            {
                $this->container->set($id, $service);
            }

            public function addGateway(string $referenceName, object $gateway): void
            {
                $this->container->set($referenceName, $gateway);
            }
        };

        foreach ($objectsToRegister as $referenceName => $object) {
            $container->set($referenceName, $object);
        }

        return EcotoneLiteConfiguration::createWithConfiguration(
            $rootCatalog,
            $container,
            $configuration,
            $configurationVariables,
            $cacheConfiguration
        );
    }
}