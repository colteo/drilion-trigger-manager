<?php
namespace DrilionTrigger\Core;

// Installer.php
class Installer {
    public function install() {
        // Inizializza le impostazioni di default
        $default_settings = [
            'plugin_enabled' => false,
            'user_meta_buoni_piatto' => [
                'enabled' => false,
                'webhook_url' => '',
                'notes' => ''
            ],
            'user_meta_punti_esperienza' => [
                'enabled' => false,
                'webhook_url' => '',
                'notes' => ''
            ],
            'user_meta_birth_date' => [
                'enabled' => false,
                'webhook_url' => '',
                'notes' => ''
            ],
            'user_creation' => [
                'enabled' => false,
                'webhook_url' => '',
                'notes' => ''
            ],
        ];

        // Aggiunge le opzioni solo se non esistono già
        if (false === get_option('drilion_trigger_settings')) {
            add_option('drilion_trigger_settings', $default_settings);
        }

        // Crea tabelle personalizzate se necessario
        $this->createTables();
    }

    public function uninstall() {
        // Rimuovi le opzioni solo se necessario
        // delete_option('drilion_trigger_settings');
        
        // Rimuovi le tabelle personalizzate se necessario
        // $this->dropTables();
    }

    private function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Esempio di creazione tabella per il logging
        $table_name = $wpdb->prefix . 'drilion_webhook_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            trigger_id varchar(50) NOT NULL,
            webhook_url varchar(255) NOT NULL,
            data longtext NOT NULL,
            response_code int(11),
            response_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function dropTables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'drilion_webhook_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}