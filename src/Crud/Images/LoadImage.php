<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\Form;
use Generator;
use Psr\Http\Message\ServerRequestInterface;

interface LoadImage
{
    public function getForm(): Form;

    public function getName(): string;

    public function getExtension(): string;

    public function getTemplatePath(string $templateRootPath): string;

    public function upload(ServerRequestInterface $request): Generator;
}
