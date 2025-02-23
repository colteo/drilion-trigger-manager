<?php
namespace DrilionTrigger\Triggers;

class BirthDateTrigger extends AbstractTrigger {
    private $old_value = null;

    public function getId(): string {
        return 'user_meta_birth_date';
    }

    public function getName(): string {
        return 'User Birth Date Change';
    }

    public function getDescription(): string {
        return 'Trigger when user birth date is changed';
    }

    public function initialize(): void {
        // Hook che viene eseguito PRIMA dell'aggiornamento
        add_action('update_user_meta', [$this, 'beforeMetaUpdate'], 10, 4);
        
        // Hook che viene eseguito DOPO l'aggiornamento
        add_action('updated_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
    }

    public function beforeMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key === 'billing_birth_date') {
            // Salviamo il vecchio valore
            $this->old_value = get_user_meta($object_id, 'billing_birth_date', true);
        }
    }

    public function handleMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key !== 'billing_birth_date' || !$this->isEnabled()) {
            return;
        }

        $user = get_user_by('id', $object_id);
        if (!$user) {
            return;
        }

        $data = [
            'trigger_id' => $this->getId(),
            'user_id' => $object_id,
            'email' => $user->user_email,
            'birth_date' => $meta_value,
            'timestamp' => current_time('mysql')
        ];

        $this->sendWebhook($data);
        
        // Reset dell'old_value dopo l'invio
        $this->old_value = null;
    }
}