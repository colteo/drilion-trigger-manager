<?php
namespace DrilionTrigger\Triggers;

class UserCreationTrigger extends AbstractTrigger {
    
    public function getId(): string {
        return 'user_creation';
    }

    public function getName(): string {
        return 'User Creation';
    }

    public function getDescription(): string {
        return 'Trigger when a new user is created in WordPress';
    }

    public function initialize(): void {
        // Hook che intercetta la creazione di un nuovo utente
        add_action('user_register', [$this, 'scheduleUserCheck'], 10, 1);
        
        // Hook aggiuntivo per intercettare anche gli utenti creati tramite WooCommerce
        add_action('woocommerce_created_customer', [$this, 'scheduleUserCheck'], 10, 1);
        
        // Hook per eseguire la verifica finale e inviare i dati
        add_action('drilion_check_new_user_data', [$this, 'handleUserCreation'], 10, 2);
    }
    
    public function scheduleUserCheck($user_id) {
        if (!$this->isEnabled()) {
            return;
        }
        
        // Determina la fonte di creazione dell'utente
        $source = $this->determineSource();
        
        // Anche una seconda verifica dopo 1 minuto per casi in cui l'inizializzazione è più lenta
        wp_schedule_single_event(time() + 60, 'drilion_check_new_user_data', [$user_id, $source]);
    }

    public function handleUserCreation($user_id, $source) {
        if (!$this->isEnabled()) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Verifica e recupera i meta relativi ai punti
        // Force fresh meta value from database to avoid caching issues
        $buoni_piatto = get_user_meta($user_id, 'lws_wre_points_buoni_piatto', true);
        $punti_esperienza = get_user_meta($user_id, 'lws_wre_points_programma_fedelta', true);
        $birth_date = get_user_meta($user_id, 'billing_birth_date', true);
        
        // Formatta la data di nascita se presente (nel formato GG/MM/AAAA come fatto in BirthDateTrigger)
        $formatted_birth_date = '';
        if (!empty($birth_date)) {
            $date_parts = explode('-', $birth_date);
            if (count($date_parts) === 3) {
                $formatted_birth_date = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
            } else {
                $formatted_birth_date = $birth_date;
            }
        }
        
        // Raccogli i dati dell'utente
        $data = [
            'trigger_id' => $this->getId(),
            'user_id' => $user_id,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'role' => $user->roles[0] ?? 'unknown',
            'registration_date' => $user->user_registered,
            'source' => $source,
            'has_buoni_piatto' => !empty($buoni_piatto),
            'buoni_piatto' => $buoni_piatto ?: 0,
            'has_punti_esperienza' => !empty($punti_esperienza),
            'punti_esperienza' => $punti_esperienza ?: 0,
            'has_birth_date' => !empty($birth_date),
            'birthday' => $formatted_birth_date,
            'timestamp' => current_time('mysql')
        ];

        // Aggiungi dati WooCommerce se disponibili
        if (function_exists('wc_get_customer_billing_email')) {
            $data['billing_first_name'] = get_user_meta($user_id, 'billing_first_name', true);
            $data['billing_last_name'] = get_user_meta($user_id, 'billing_last_name', true);
            $data['billing_email'] = get_user_meta($user_id, 'billing_email', true);
            $data['billing_phone'] = get_user_meta($user_id, 'billing_phone', true);
        }

        // Verifica se abbiamo già inviato un webhook per questo utente
        $webhook_sent = get_user_meta($user_id, '_drilion_user_creation_webhook_sent', true);
        
        // Se non abbiamo ancora inviato il webhook, o se è passata più di un'ora dall'ultimo invio
        if (empty($webhook_sent) || (time() - (int)$webhook_sent) > 3600) {
            // Invia i dati all'endpoint webhook
            $result = $this->sendWebhook($data);
            
            if ($result) {
                // Segna che abbiamo inviato il webhook per questo utente
                update_user_meta($user_id, '_drilion_user_creation_webhook_sent', time());
            }
        }
    }

    private function determineSource(): string {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        
        foreach ($backtrace as $trace) {
            $class = $trace['class'] ?? '';
            $function = $trace['function'] ?? '';
            $file = $trace['file'] ?? '';

            // Utente creato tramite WooCommerce
            if (strpos($class, 'WC_') !== false || 
                strpos($file, 'woocommerce') !== false) {
                return 'woocommerce';
            }

            // Utente creato dall'admin
            if (strpos($file, 'user-new.php') !== false || 
                strpos($file, 'wp-admin') !== false) {
                return 'admin';
            }

            // Utente creato tramite API REST
            if (strpos($class, 'WP_REST') !== false || 
                defined('REST_REQUEST') && REST_REQUEST) {
                return 'api';
            }
            
            // Utente creato tramite registrazione frontend
            if (strpos($file, 'wp-login.php') !== false || 
                strpos($function, 'register_new_user') !== false) {
                return 'registration';
            }
        }

        // Se non si riesce a determinare, ritorna 'unknown'
        return 'unknown';
    }
}