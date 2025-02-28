<?php
namespace DrilionTrigger\Triggers;

class PuntiEsperienzaTrigger extends AbstractTrigger {
    // Array dei livelli di esperienza
    private $experience_levels = [
        1 => 1,      // Livello 1: Esploratore di Pasti (1 EXP)
        2 => 500,    // Livello 2: Collezionista di Vaschette (500 EXP)
        3 => 1000,   // Livello 3: Esperto di Piatti Pronti (1000 EXP)
        4 => 2000,   // Livello 4: Mago dei Microonde (2000 EXP)
        5 => 3000    // Livello 5: Guru del Pranzo al Volo (3000 EXP)
    ];

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
            // Calcola il livello attuale e i punti mancanti al livello successivo
            $current_exp = intval($webhook_data['punti_esperienza']);
            $current_level = $this->getCurrentLevel($current_exp);
            $next_level = $current_level + 1;
            
            // Calcola punti mancanti al livello successivo
            $points_to_next_level = 0;
            if ($next_level <= 5) { // Verifica che non siamo già al livello massimo
                $points_to_next_level = $this->experience_levels[$next_level] - $current_exp;
            }
            
            // Prepara i dati per il webhook
            $data = [
                'trigger_id' => $this->getId(),
                'user_id' => $webhook_data['user_id'],
                'email' => $webhook_data['email'],
                'punti_esperienza' => $webhook_data['punti_esperienza'],
                'current_level' => $current_level,
                'punti_per_livello_successivo' => $points_to_next_level,
                'is_max_level' => ($current_level == 5),
                'timestamp' => $webhook_data['timestamp']
            ];
            
            // Invia il webhook
            $this->sendWebhook($data);
        }
        
        // Pulisci l'opzione dopo aver elaborato tutti i webhook
        delete_option('drilion_pending_pe_webhooks');
    }
    
    /**
     * Calcola il livello attuale in base ai punti esperienza
     * 
     * @param int $exp_points Punti esperienza dell'utente
     * @return int Livello attuale dell'utente
     */
    private function getCurrentLevel($exp_points) {
        $level = 1; // Livello predefinito
        
        foreach ($this->experience_levels as $lvl => $req_points) {
            if ($exp_points >= $req_points) {
                $level = $lvl;
            } else {
                break;
            }
        }
        
        return $level;
    }
}