<?php

namespace App\Domain\News\Enums;

enum ArticleVisibility: string
{
    case Draft = 'draft';
    case Internal = 'internal';
    case Public = 'public';
}
