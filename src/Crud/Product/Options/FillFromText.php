<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Options;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\OptionKeyRepository;
use EnjoysCMS\Module\Catalog\Repositories\OptionValueRepository;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FillFromText
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    private ObjectRepository|EntityRepository|OptionKeyRepository $keyRepository;
    private ObjectRepository|EntityRepository|OptionValueRepository $valueRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->entityManager->getRepository(Product::class);
        $this->keyRepository = $this->entityManager->getRepository(OptionKey::class);
        $this->valueRepository = $this->entityManager->getRepository(OptionValue::class);
    }

    /**
     * @throws NoResultException
     */
    public function __invoke()
    {
        /** @var Product $product */
        $product = $this->productRepository->find($this->serverRequest->post('id', 0));
        if ($product === null) {
            throw new NoResultException();
        }

        $dataRaw = $this->serverRequest->post('text');

        $options = $this->parse($dataRaw);

        foreach ($options as $option) {
            if(empty($option['option']) || empty($option['value'])){
                continue;
            }
            $optionKey = $this->keyRepository->getOptionKey($option['option'], $option['unit']);
            foreach (explode(',', $option['value']) as $value) {
                $optionValue = $this->valueRepository->getOptionValue($value, $optionKey);
                $this->entityManager->persist($optionValue);
                $product->addOption($optionValue);
            }
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->redirectToProductOptionsPage($product);

    }

    private function parse(string $dataRaw)
    {
        $dataSanitize = array_filter(
            array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", \trim($dataRaw)))),
            function ($item) {
                return !empty($item);
            }
        );
        $template = "/^(.+[^()])(\((.*[^()])\))?:(.+)/";
        $result = [];
        foreach ($dataSanitize as $item) {
            preg_match($template, $item, $matches);
            $result[] = [
                'option' => \trim($matches[1]),
                'unit' => \trim($matches[3]),
                'value' => \trim($matches[4])
            ];
        }
        return $result;
    }

    private function redirectToProductOptionsPage(Product $product)
    {
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/options', ['id' => $product->getId()]));
    }
}
