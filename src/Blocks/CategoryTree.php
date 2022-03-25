<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;


final class CategoryTree extends AbstractBlock
{
    /**
     * @var \EnjoysCMS\Module\Catalog\Repositories\Category
     */
    private $categoryRepository;
    /**
     * @var Environment
     */
    private $twig;

    private ServerRequestInterface $request;
    private string $templatePath;

    public function __construct(ContainerInterface $container, Entity $block)
    {
        parent::__construct($container, $block);
        $this->categoryRepository = $this->container->get(EntityManager::class)->getRepository(Category::class);
        $this->twig = $this->container->get(Environment::class);
        $this->request = $this->container->get(ServerRequestInterface::class);
        $this->templatePath = (string)$this->getOption('template');
    }


    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @return string
     */
    public function view()
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
