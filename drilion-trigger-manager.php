<?php
/**
 * Plugin Name: Drilion Trigger Manager
 * Description: Gestione webhook per trigger su modifiche WordPress/WooCommerce
 * Version: 1.1.2
 * Author: Your Name
 * License: GPL v2 or later
 */

namespace DrilionTrigger;

// Previeni l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Nascondi i warning/notice
@ini_set('display_errors', 0);

// Definizione costanti
// define('DRILION_TRIGGER_VERSION', '1.0.0');
define('DRILION_TRIGGER_PATH', plugin_dir_path(__FILE__));
define('DRILION_TRIGGER_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'DrilionTrigger\\';
    $base_dir = DRILION_TRIGGER_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Inizializzazione del plugin
class Plugin {
    private static $instance = null;
    private $container;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initContainer();
        $this->initHooks();
    }

    private function initContainer() {
        $this->container = new Core\Container();
        
        // Registra i servizi base
        $this->container->register('config', new Core\Config());
        $this->container->register('database', new Core\Database());
        $this->container->register('webhook_service', new Services\WebhookService());
        $this->container->register('trigger_manager', new Services\TriggerManager());
        
    }

    private function initHooks() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Inizializza i trigger
        $this->initializeTriggers();
    }

    private function initializeTriggers() {
        $triggerManager = $this->container->get('trigger_manager');
        
        // Registra i trigger predefiniti
        $triggerManager->registerTrigger(new Triggers\BuoniPiattoTrigger());
        $triggerManager->registerTrigger(new Triggers\PuntiEsperienzaTrigger());
        $triggerManager->registerTrigger(new Triggers\BirthDateTrigger());
    }

    public function addAdminMenu() {
        add_menu_page(
            'Drilion Trigger Manager',
            'Trigger Manager',
            'manage_options',
            'drilion-trigger-manager',
            [$this, 'renderAdminPage'],
            'dashicons-controls-repeat'
        );
    }

    public function renderAdminPage() {
        $view = new Admin\Views\MainView($this->container);
        $view->render();
    }

    public function registerSettings() {
        register_setting('drilion_trigger_options', 'drilion_trigger_settings');
    }
}

// Inizializza il plugin
add_action('plugins_loaded', function() {
    Plugin::getInstance();
});

// Attivazione
register_activation_hook(__FILE__, function() {
    $installer = new Core\Installer();
    $installer->install();
});

// Disattivazione
register_deactivation_hook(__FILE__, function() {
    $installer = new Core\Installer();
    $installer->uninstall();
});