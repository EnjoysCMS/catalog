<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Blocks as Entity;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Container\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;


final class CategoryTree  extends AbstractBlock
{
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(ContainerInterface $container, Entity $block)
    {
        parent::__construct($container, $block);
        $this->categoryRepository = $this->container->get(EntityManager::class)->getRepository(Category::class);
        $this->twig = $this->container->get(Environment::class);
       $this->templatePath = $this->getOption('template');
    }

    public function view()
    {

        return $this->twig->render($this->templatePath, [
             'tree' => $this->categoryRepository->getNodes(null, ['status' => true]),
        ]);
    }

    public static function getMeta(): ?array
    {
        return Yaml::parseFile(__DIR__.'/../../blocks.yml')[__CLASS__];
    }
}