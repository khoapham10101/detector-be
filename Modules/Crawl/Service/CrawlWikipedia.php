<?php

namespace Modules\Crawl\Service;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\RequestException;

class CrawlWikipedia implements CrawlServiceInterface
{
    protected $client;

    /**
     * Constructor method.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Crawl data from wikipedia.
     *
     * @param  string  $url
     * @param  boolean $force
     * @return array|[]
     */
    public function crawl(string $url = '', bool $force = false): array
    {
        $html = $this->fetchHtml($url, $force);
        $parseHtml =  $this->parseHtml($html);
        return $this->htmlCollection($parseHtml);
    }

    /**
     * Reformat data into collection.
     *
     * @param  array $parseHtml
     * @return array|[]
     */
    public function htmlCollection(array $parseHtml = []): array
    {
        $collections = [];
        $count = 0;
        foreach( $parseHtml['data'] as $items) {
            foreach ($items as $key => $item) {
                if (isset($parseHtml['header'][$key])) {
                    $collections[$count][$parseHtml['header'][$key]] = $item;
                }
                else {
                    $collections[$count][$key] = $item;
                }
                
            }
            $count++;
        }
        return $collections;
    }

    /**
     * Fetch html content.
     *
     * @param  string  $url
     * @param  boolean $force
     * @return string|''
     */
    public function fetchHtml(string $url = '', bool $force = false): string
    {
        if ($force) {
            Cache::forget('wikipedia:' . $url);
        }
        return Cache::remember(
            'wikipedia:' . $url, now()->addDay(30), function () use ($url) {
                try {
                    $response = $this->client->request('GET', $url);
                    return $response->getBody()->getContents();
                } catch (RequestException $e) {
                    // Guzzle-specific exceptions
                    abort(400, 'No table found');
                } catch (\Exception $e) {
                    // General PHP exceptions
                    abort(500, 'An error occurred');
                }
            }
        );
    }

    /**
     * Parse HTML and extract table data
     *
     * @param  string $html
     * @return array|[]
     */
    public function parseHtml(string $html = ''): array
    {
        $crawler = new Crawler($html);
        $table = $crawler->filter('table')->first();
        $data = [];
        $header = [];

        $table->filter('tr')->each(
            function (Crawler $node) use (&$data, &$header) {
                $columns = $node->filter('td');
                if ($columns->count() >= 4) {
                    $data[] = $this->extractRowData($columns);
                }

                $headerColumn = $node->filter('th');
            
                if ($headerColumn->count() >= 4) {
                    $header = $this->extractRowData($headerColumn);
                }
            }
        );

        return [
            'data' => $data,
            'header' => $header,
        ];
    }

    /**
     * Extract data from the given Crawler node.
     *
     * @param  Crawler $columns
     * @return array|[]
     */
    public function extractRowData(Crawler $columns): array
    {
        $rowData = [];
        $columns->each(
            function (Crawler $columnNode, $j) use (&$rowData) {
                $rowData["column_" . ($j + 1)] = trim($columnNode->text());
                if ($j == 0) {
                    preg_match('/\b\d+(\.\d+)?\b/', $rowData["column_" . ($j + 1)], $match);
                    $rowData["column_number_" . ($j + 1)] = $match[0] ?? null;
                }

                if ($columnNode->filter('img')->count() > 0) {
                    $rowData["src_column_" . ($j + 1)] = $columnNode->filter('img')->attr('src');
                }
            }
        );
        return $rowData;
    }
}
