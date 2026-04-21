<?php

namespace App\Services;

use Meilisearch\Client;

class SearchService
{
    private $client;
    private $indexName = 'posts';

    public function __construct()
    {
        $host = $_ENV['MEILISEARCH_HOST'] ?? 'http://localhost:7700';
        $key = $_ENV['MEILISEARCH_KEY'] ?? '';

        if (!empty($key)) {
            try {
                $this->client = new Client($host, $key);
            } catch (\Exception $e) {
                error_log("Meilisearch connection error: " . $e->getMessage());
            }
        }
        
        add_action('save_post', [$this, 'syncPost'], 10, 3);
        add_action('deleted_post', [$this, 'deletePost']);
    }

    public function getIndex()
    {
        if (!$this->client) return null;
        return $this->client->index($this->indexName);
    }

    public function syncPost($post_id, $post, $update)
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || $post->post_status !== 'publish') {
            return;
        }

        if (!in_array($post->post_type, ['post', 'galeria'])) {
            return;
        }

        $index = $this->getIndex();
        if (!$index) return;

        $categories = wp_get_post_categories($post_id, ['fields' => 'names']);
        
        $document = [
            'id' => $post_id,
            'title' => $post->post_title,
            'content' => wp_strip_all_tags($post->post_content),
            'excerpt' => wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post_id),
            'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium') ?: '',
            'date' => strtotime($post->post_date),
            'category' => $categories
        ];

        try {
            $index->addDocuments([$document]);
        } catch (\Exception $e) {
            error_log("Meilisearch sync error on post $post_id: " . $e->getMessage());
        }
    }

    public function deletePost($post_id)
    {
        $index = $this->getIndex();
        if (!$index) return;
        try {
            $index->deleteDocument($post_id);
        } catch (\Exception $e) {
            error_log("Meilisearch delete error on post $post_id: " . $e->getMessage());
        }
    }

    public function search($query, $limit = 6)
    {
        $index = $this->getIndex();
        if (!$index) return [];

        try {
            $result = $index->search($query, [
                'limit' => $limit,
                'attributesToHighlight' => ['title', 'excerpt']
            ]);
            return $result->getHits();
        } catch (\Exception $e) {
            error_log("Meilisearch query error: " . $e->getMessage());
            return [];
        }
    }
}
