<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Module\Catalog\Entity;
use EnjoysCMS\Module\Catalog\Repository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class CategoryTree
{

    private Repository\Category $categoryRepository;

    private Environment $twig;

    private ServerRequestInterface $request;
    private string $templatePath;

    /**
     * @param ContainerInterface&FactoryInterface $container
     * @param Block $block
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(private ContainerInterface $container, Block $block)
    {
        $this->categoryRepository = $this->container->get(EntityManager::class)->getRepository(
            Entity\Category::class
        );
        $this->twig = $this->container->get(Environment::class);
        $this->request = $this->container->get(ServerRequestInterface::class);
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
                'currentSlug' => $this->request->getAttribute('slug')
            ]
        );
    }

}
