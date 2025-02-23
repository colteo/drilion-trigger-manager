<?php
namespace DrilionTrigger\Triggers;

class PuntiEsperienzaTrigger extends AbstractTrigger {
    private $old_value = null;

    public function getId(): string {
        return 'user_meta_punti_esperienza';
    }

    public function getName(): string {
        return 'Punti Esperienza Change';
    }

    public function getDescription(): string {
        return 'Trigger when user Punti Esperienza are changed';
    }

    public function initialize(): void {
        // Hook che viene eseguito PRIMA dell'aggiornamento
        add_action('update_user_meta', [$this, 'beforeMetaUpdate'], 10, 4);
        
        // Hook che viene eseguito DOPO l'aggiornamento
        add_action('updated_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
    }

    public function beforeMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key === 'lws_wre_points_programma_fedelta') {
            // Salviamo il vecchio valore
            $this->old_value = get_user_meta($object_id, 'lws_wre_points_programma_fedelta', true);
        }
    }

    public function handleMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key !== 'lws_wre_points_programma_fedelta' || !$this->isEnabled()) {
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
            'punti_esperienza' => $meta_value,
            'timestamp' => current_time('mysql')
        ];

        $this->sendWebhook($data);
        
        // Reset dell'old_value dopo l'invio
        $this->old_value = null;
    }
}