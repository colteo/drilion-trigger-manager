<?php
namespace DrilionTrigger\Triggers;

interface TriggerInterface {
    public function getId(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function initialize(): void;
    public function isEnabled(): bool;
}