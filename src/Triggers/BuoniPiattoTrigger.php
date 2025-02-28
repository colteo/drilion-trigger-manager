<?php
namespace DrilionTrigger\Triggers;

class BuoniPiattoTrigger extends AbstractTrigger {
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
        // Hook che viene eseguito DOPO l'aggiornamento
        add_action('updated_user_meta', [$this, 'handleMetaUpdate'], 10, 4);
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
        
        // Calcola l'equivalente in euro (100 BP = 1€)
        $buoni_piatto_value = floatval($meta_value);
        $euro_equivalent = round($buoni_piatto_value / 100, 2);
        
        $data = [
            'trigger_id' => $this->getId(),
            'user_id' => $object_id,
            'email' => $user->user_email,
            'buoni_piatto' => $meta_value,
            'buoni_piatto_euro' => $euro_equivalent,
            'source' => $source,
            'timestamp' => current_time('mysql')
        ];

        $this->sendWebhook($data);
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