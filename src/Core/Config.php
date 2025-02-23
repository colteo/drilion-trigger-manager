<?php
namespace DrilionTrigger\Core;

// Config.php
class Config {
    private $settings = [];

    public function __construct() {
        $this->settings = get_option('drilion_trigger_settings', []);
    }

    public function get(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }

    public function save() {
        update_option('drilion_trigger_settings', $this->settings);
    }
}