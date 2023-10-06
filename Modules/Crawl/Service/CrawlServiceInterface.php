<?php

namespace Modules\Crawl\Service;

use Symfony\Component\DomCrawler\Crawler;

interface CrawlServiceInterface
{
    public function crawl(string $url = '', bool $force = false): array;

    public function fetchHtml(string $url = '', bool $force = false): string;

    public function parseHtml(string $html = ''): array;

    public function extractRowData(Crawler $columns): array;

    public function htmlCollection(array $parseHtml = []): array;
}
