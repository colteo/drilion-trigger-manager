<?php
namespace DrilionTrigger\Triggers;

abstract class AbstractTrigger implements TriggerInterface {
    protected $webhook_url;
    protected $notes;
    protected $enabled;

    public function __construct() {
        $this->loadSettings();
    }

    protected function loadSettings() {
        $settings = get_option('drilion_trigger_settings', []);
        $trigger_id = $this->getId();
        
        if (isset($settings[$trigger_id])) {
            $this->webhook_url = $settings[$trigger_id]['webhook_url'] ?? '';
            $this->notes = $settings[$trigger_id]['notes'] ?? '';
            $this->enabled = $settings[$trigger_id]['enabled'] ?? false;
        }
    }

    public function isEnabled(): bool {
        $plugin_settings = get_option('drilion_trigger_settings', []);
        $plugin_enabled = $plugin_settings['plugin_enabled'] ?? false;
        
        return $plugin_enabled && $this->enabled;
    }

    protected function sendWebhook(array $data) {
        if (!$this->isEnabled() || empty($this->webhook_url)) {
            return false;
        }

        $response = wp_remote_post($this->webhook_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('Drilion Trigger webhook error: ' . $response->get_error_message());
            return false;
        }

        return true;
    }
}