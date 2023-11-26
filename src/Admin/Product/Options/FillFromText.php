<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Repository\OptionKeyRepository;
use EnjoysCMS\Module\Catalog\Repository\OptionValueRepository;
use EnjoysCMS\Module\Catalog\Repository\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;

use function trim;

final class FillFromText
{
    private EntityRepository|ProductRepository $productRepository;
    private EntityRepository|OptionKeyRepository $keyRepository;
    private EntityRepository|OptionValueRepository $valueRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RedirectInterface $redirect,
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->keyRepository = $this->em->getRepository(OptionKey::class);
        $this->valueRepository = $this->em->getRepository(OptionValue::class);
    }

    /**
     * @throws NoResultException
     * @throws ORMException
     */
    public function __invoke(): void
    {
        /** @var Product $product */
        $product = $this->productRepository->find(
            $this->request->getParsedBody()['id'] ?? 0
        ) ?? throw new NoResultException();


        $dataRaw = $this->request->getParsedBody()['text'] ?? null;
        $template = $this->request->getParsedBody()['template'] ?? "/^(.+[^()])(\((.*[^()])\))?:(.+)/";
        $options = $this->parse($dataRaw, $template);

        foreach ($options as $option) {
            if (empty($option['option']) || empty($option['value'])) {
                continue;
            }
            $optionKey = $this->keyRepository->getOptionKey($option['option'], $option['unit']);
            foreach (explode(',', $option['value']) as $value) {
                $optionValue = $this->valueRepository->getOptionValue($value, $optionKey);
                $this->em->persist($optionValue);
                $product->addOption($optionValue);
            }
        }

        $this->em->persist($product);
        $this->em->flush();

        $this->redirectToProductOptionsPage($product);
    }

    /**
     * @return string[][]
     *
     * @psalm-return list<array{option: string, unit: string, value: string}>
     */
    private function parse(string $dataRaw, $template): array
    {
        $dataSanitize = array_filter(
            array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", trim($dataRaw)))),
            function ($item) {
                return !empty($item);
            }
        );

        $result = [];
        foreach ($dataSanitize as $item) {
            preg_match($template, $item, $matches);
            $result[] = [
                'option' => trim($matches[1]),
                'unit' => trim($matches[3]),
                'value' => trim($matches[4])
            ];
        }
        return $result;
    }

    private function redirectToProductOptionsPage(Product $product): void
    {
        $this->redirect->toRoute(
            '@catalog_product_options',
            ['product_id' => $product->getId()],
            emit: true
        );
    }
}
