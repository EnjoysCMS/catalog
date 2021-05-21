<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use Enjoys\Config\Config;
use Psr\Container\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

final class ModuleConfig
{

    private ?array $config;
    private Config $containerConfig;

    public function __construct(ContainerInterface $container)
    {
        $this->containerConfig = $container->get('Config');
        $this->initConfig();
    }

    private function initConfig($break = false)
    {
        $this->config = $this->containerConfig->getConfig('module/catalog');

        if ($break === true) {
            return;
        }
        if ($this->config === null) {
            $this->containerConfig->addConfig(
                __DIR__ . '/../config.yml',
                ['flags' => Yaml::PARSE_CONSTANT],
                Config::YAML
            );

            $this->initConfig(true);
        }
    }

    public function get(string $key): string
    {

        if(array_key_exists($key, $this->config)){
            return $this->config[$key];
        }

        throw new \InvalidArgumentException(sprintf('Param %s not found', $key));
    }
}