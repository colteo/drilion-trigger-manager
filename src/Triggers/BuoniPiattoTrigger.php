<?php
namespace DrilionTrigger\Triggers;

class BuoniPiattoTrigger extends AbstractTrigger {
    private $old_value = null;

    public function getId(): string {
        return 'user_meta_buoni_piatto';
    }

    public function getName(): string {
        return 'Buoni Piatto Points Change';
    }

    public function getDescription(): string {
        return 'Trigger when user Buoni Piatto points are changed';
    }

    public function initialize(): void {
        // Hook che viene eseguito PRIMA dell'aggiornamento
        // Questo potrebbe non servire
        add_action('update_user_meta', [$this, 'beforeMetaUpdate'], 10, 4);
        
        // Hook che viene eseguito DOPO l'aggiornamento
        add_action('updated_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
    }

    public function beforeMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key === 'lws_wre_points_buoni_piatto') {
            // Salviamo il vecchio valore
            $this->old_value = get_user_meta($object_id, 'lws_wre_points_buoni_piatto', true);
        }
    }

    public function handleMetaUpdate($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key !== 'lws_wre_points_buoni_piatto' || !$this->isEnabled()) {
            return;
        }

        $user = get_user_by('id', $object_id);
        if (!$user) {
            return;
        }

        // Determina l'origine della modifica
        $source = $this->determineSource();
        
        $data = [
            'trigger_id' => $this->getId(),
            'user_id' => $object_id,
            'email' => $user->user_email,
            'buoni_piatto' => $meta_value,
            'source' => $source,
            'timestamp' => current_time('mysql')
        ];

        $this->sendWebhook($data);
        
        // Pulizia del valore vecchio dopo l'invio
        unset($this->old_values[$meta_key]);
    }

    private function determineSource(): string {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        
        // Cerca specifici pattern nel backtrace
        foreach ($backtrace as $trace) {
            $class = $trace['class'] ?? '';
            $function = $trace['function'] ?? '';
            $file = $trace['file'] ?? '';

            // Pattern per ordine WooCommerce
            if (strpos($class, 'WC_Order') !== false || 
                strpos($file, 'class-wc-order') !== false ||
                strpos($file, '/woocommerce/') !== false) {
                return 'order';
            }

            // Pattern per modifica profilo
            if (strpos($file, 'user-edit.php') !== false || 
                strpos($file, 'profile.php') !== false ||
                strpos($class, 'WP_User') !== false) {
                return 'profile';
            }

            // Pattern per REST API
            if (strpos($class, 'WP_REST') !== false || 
                defined('REST_REQUEST') && REST_REQUEST) {
                return 'api';
            }
        }

        // Se non riesci a determinare la fonte, ritorna 'unknown'
        return 'unknown';
    }
}