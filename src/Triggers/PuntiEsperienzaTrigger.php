<?php
namespace DrilionTrigger\Triggers;

class PuntiEsperienzaTrigger extends AbstractTrigger {
    public function getId(): string {
        return 'user_meta_punti_esperienza';
    }

    public function getName(): string {
        return 'Punti Esperienza Change';
    }

    public function getDescription(): string {
        return 'Trigger when user Punti Esperienza are changed (with transaction locking)';
    }

    public function initialize(): void {
        // Hook che viene eseguito DOPO l'aggiornamento
        add_action('updated_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
        
        // Aggiungiamo un hook che viene eseguito alla fine della richiesta
        add_action('shutdown', [$this, 'processQueuedWebhooks']);
    }

    public function handleMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key !== 'lws_wre_points_programma_fedelta' || !$this->isEnabled()) {
            return;
        }

        $user = get_user_by('id', $object_id);
        if (!$user) {
            return;
        }
        
        // Salva i dati utente da inviare in un'opzione temporanea
        // Usa array per supportare più utenti contemporaneamente
        $pending_webhooks = get_option('drilion_pending_pe_webhooks', []);
        
        // Aggiorna o aggiungi le informazioni per questo utente
        $pending_webhooks[$object_id] = [
            'user_id' => $object_id,
            'email' => $user->user_email,
            'punti_esperienza' => $meta_value,
            'timestamp' => current_time('mysql')
        ];
        
        // Salva l'opzione aggiornata
        update_option('drilion_pending_pe_webhooks', $pending_webhooks);
        
        // Non inviare il webhook qui - sarà inviato durante shutdown
    }
    
    public function processQueuedWebhooks() {
        // Ottieni i webhook in attesa
        $pending_webhooks = get_option('drilion_pending_pe_webhooks', []);
        
        // Se non ci sono webhook in attesa, non fare nulla
        if (empty($pending_webhooks)) {
            return;
        }
        
        // Elabora ogni webhook in attesa
        foreach ($pending_webhooks as $user_id => $webhook_data) {
            // Prepara i dati per il webhook
            $data = [
                'trigger_id' => $this->getId(),
                'user_id' => $webhook_data['user_id'],
                'email' => $webhook_data['email'],
                'punti_esperienza' => $webhook_data['punti_esperienza'],
                'timestamp' => $webhook_data['timestamp']
            ];
            
            // Invia il webhook
            $this->sendWebhook($data);
        }
        
        // Pulisci l'opzione dopo aver elaborato tutti i webhook
        delete_option('drilion_pending_pe_webhooks');
    }
}