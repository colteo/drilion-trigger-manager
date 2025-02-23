<?php
namespace DrilionTrigger\Services;

use DrilionTrigger\Triggers\TriggerInterface;

class TriggerManager {
    private $triggers = [];

    public function registerTrigger(TriggerInterface $trigger) {
        $this->triggers[$trigger->getId()] = $trigger;
        $trigger->initialize();
    }

    public function getTrigger(string $id): ?TriggerInterface {
        return $this->triggers[$id] ?? null;
    }

    public function getAllTriggers(): array {
        return $this->triggers;
    }
}