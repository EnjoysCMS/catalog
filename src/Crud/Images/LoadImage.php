<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Enjoys\Forms\Form;
use Enjoys\Http\ServerRequestInterface;

interface LoadImage
{
    public function getForm(): Form;
    public function getName(): string;
    public function getExtension(): string;
    public function getFullPathFileNameWithExtension(): string;
    public function upload(ServerRequestInterface $serverRequest): void;
}
