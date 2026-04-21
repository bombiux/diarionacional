<?php

namespace App\Api;

use App\Services\SearchService;
use Timber\Timber;

class SearchEndpoint
{
    private $searchService;

    public function __construct()
    {
        $this->searchService = new SearchService();
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        register_rest_route('dbarricada/v1', '/search', [
            'methods' => 'GET',
            'callback' => [$this, 'handleSearch'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleSearch(\WP_REST_Request $request)
    {
        $query = $request->get_param('q');
        
        if (empty($query) || strlen($query) < 2) {
            return new \WP_REST_Response('', 200, ['Content-Type' => 'text/html']);
        }

        $results = $this->searchService->search($query, 6);
        
        $context = Timber::context();
        $context['search_results'] = $results;
        $context['search_query'] = $query;
        
        $html = Timber::compile('partials/search-results.twig', $context);
        
        return new \WP_REST_Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
