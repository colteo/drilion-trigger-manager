<?php
namespace DrilionTrigger\Services;

use DrilionTrigger\Triggers\TriggerInterface;

class WebhookService {
    public function validateWebhookUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function testWebhook(string $url): bool {
        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['test' => true]),
            'timeout' => 30
        ]);

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}