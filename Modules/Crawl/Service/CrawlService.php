<?php
namespace Modules\Crawl\Service;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\DomCrawler\Crawler;

class CrawlService
{

    /**
     * Crawl data wikipedia
     *
     * @return array
     */
    public static function crawl(): array
    {
        $html = Cache::get('wikipedia', '');
        if ($html == '') {
            $url = 'https://en.wikipedia.org/wiki/Women%27s_high_jump_world_record_progression';
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);

            $html = Cache::remember('wikipedia', now()->addDay(30), function () use ($response) {
                return $response->getBody()->getContents();
            });
        }

        $crawler = new Crawler($html);
        $table = $crawler->filter('table')->first();
        $data = [];

        $table->filter('tr')->each(function (Crawler $node, $i) use (&$data) {
            $columns = $node->filter('td');
            if ($columns->count() >= 2) {
                $firstColumn = trim($columns->eq(0)->text());
                $secondColumn = trim($columns->eq(1)->text());
                $thirdColumn = trim($columns->eq(2)->text());
                $fourthColumn = trim($columns->eq(3)->text());
                preg_match('/\b\d+(\.\d+)?\b/', $firstColumn, $match);
                $numbers = $match[0] ?? null;
                $data[] = [
                    'first_column' => $firstColumn,
                    'first_column_number' => $numbers,
                    'second_column' => $secondColumn,
                    'third_column' => $thirdColumn,
                    'fourth_column' => $fourthColumn
                ];
            }
        });

        return $data;
    }
}
