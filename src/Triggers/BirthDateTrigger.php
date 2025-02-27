<?php
namespace DrilionTrigger\Triggers;

class BirthDateTrigger extends AbstractTrigger {
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
        // Hook che viene eseguito DOPO l'aggiornamento
        add_action('updated_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
    
        // Hook che viene eseguito quando il meta viene aggiunto per la prima volta
        add_action('added_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
    }

    public function handleMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key !== 'billing_birth_date' || !$this->isEnabled()) {
            return;
        }

        $user = get_user_by('id', $object_id);
        if (!$user) {
            return;
        }

        $date_parts = explode('-', $meta_value);
        if (count($date_parts) === 3) {
            $formatted_date = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
        } else {
            // Gestione del caso in cui il formato non sia quello atteso
            $formatted_date = $meta_value;
            error_log('Formato data non valido: ' . $meta_value);
        }

        $data = [
            'trigger_id' => $this->getId(),
            'user_id' => $object_id,
            'email' => $user->user_email,
            'birthday' => $formatted_date,
            'timestamp' => current_time('mysql')
        ];

        $this->sendWebhook($data);
    }
}