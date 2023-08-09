<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Setting
{

    private LoggerInterface $logger;

    private static ?array $cache = null;

    public function __construct(private EntityManager $em, LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }


    public function getAll(): ?array
    {
        if (Setting::$cache === null) {
            Setting::$cache = $this->fetchSetting();
        }
        return self::$cache;
    }

    public function get(string $var, mixed $default = null): mixed
    {
        if (Setting::$cache === null) {
            Setting::$cache = $this->fetchSetting();
        }

        if (array_key_exists($var, Setting::$cache)) {
            return Setting::$cache[$var];
        }

        $this->logger->debug(sprintf('Parameter `%s` not found! Return default value', $var));

        return $default;
    }

    /**
     * @return array<string, null|string>
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function fetchSetting(): array
    {
        /** @var \EnjoysCMS\Core\Setting\Repository\Setting $repositoryCoreSetting */
        $repositoryCoreSetting = $this->em->getRepository(\EnjoysCMS\Core\Setting\Entity\Setting::class);

        /** @var \EnjoysCMS\Module\Catalog\Repositories\Setting $repositoryCatalogSetting */
        $repositoryCatalogSetting = $this->em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Setting::class);

        return array_merge($repositoryCoreSetting->findAllKeyVar(), $repositoryCatalogSetting->findAllKeyVar());
    }


}
