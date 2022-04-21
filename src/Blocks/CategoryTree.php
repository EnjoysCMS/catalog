<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Catalog\Entities;
use EnjoysCMS\Module\Catalog\Repositories;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class CategoryTree extends AbstractBlock
{

    private Repositories\Category $categoryRepository;

    private Environment $twig;

    private ServerRequestWrapper $requestWrapper;
    private string $templatePath;

    /**
     * @param ContainerInterface&FactoryInterface $container
     * @param Entity $block
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(private ContainerInterface $container, Entity $block)
    {
        parent::__construct($block);
        $this->categoryRepository = $this->container->get(EntityManager::class)->getRepository(
            Entities\Category::class
        );
        $this->twig = $this->container->get(Environment::class);
        $this->requestWrapper = $this->container->get(ServerRequestWrapper::class);
        $this->templatePath = (string)$this->getOption('template');
    }


    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }


    /**
     * @throws SyntaxError
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NoResultException
     */
    public function view(): string
    {
        return $this->twig->render(
            $this->templatePath,
            [
                'tree' => $this->categoryRepository->getChildNodes(null, ['status' => true]),
                'blockOptions' => $this->getOptions(),
                'currentSlug' => $this->requestWrapper->getAttributesData()->get('slug')
            ]
        );
    }

}
