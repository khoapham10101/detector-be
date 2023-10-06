<?php

namespace Modules\Crawl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Crawl\Service\CrawlService;

class CrawlController extends Controller
{
    /**
     * Crawl function
     *
     * @return JsonResponse
     */
    public function crawlData(): JsonResponse
    {
        $data = CrawlService::crawl();

        return response()->json($data);
    }

}
