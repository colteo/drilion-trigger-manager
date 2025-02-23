<?php
namespace DrilionTrigger\Core;

// Container.php
class Container {
    private $services = [];

    public function register(string $id, $service) {
        $this->services[$id] = $service;
    }

    public function get(string $id) {
        if (!isset($this->services[$id])) {
            throw new \Exception("Service $id not found in container.");
        }
        return $this->services[$id];
    }
}