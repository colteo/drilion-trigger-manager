<?php
namespace DrilionTrigger\Admin\Views;

class MainView {
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function render() {
        $trigger_manager = $this->container->get('trigger_manager');
        $settings = get_option('drilion_trigger_settings', []);
        $plugin_enabled = $settings['plugin_enabled'] ?? false;
        ?>
        <div class="wrap">
            <h1>Drilion Trigger Manager</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('drilion_trigger_options'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Plugin</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="drilion_trigger_settings[plugin_enabled]" 
                                       value="1" 
                                       <?php checked($plugin_enabled, true); ?>>
                                Enable plugin
                            </label>
                        </td>
                    </tr>
                </table>

                <h2>Configured Triggers</h2>
                
                <!-- Aggiungiamo stili inline per controllare le larghezze delle colonne -->
                <style>
                    .drilion-triggers-table th:nth-child(1) { width: 20%; }
                    .drilion-triggers-table th:nth-child(2) { width: 10%; }
                    .drilion-triggers-table th:nth-child(3) { width: 45%; }
                    .drilion-triggers-table th:nth-child(4) { width: 25%; }
                    .drilion-triggers-table td { vertical-align: top; padding: 15px 10px; }
                    .drilion-triggers-table .webhook-url-input { width: 100%; min-width: 300px; }
                </style>

                <table class="widefat drilion-triggers-table">
                    <thead>
                        <tr>
                            <th style="width:20%">Trigger</th>
                            <th style="width:10%">Status</th>
                            <th style="width:45%">Webhook URL</th>
                            <th style="width:25%">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trigger_manager->getAllTriggers() as $trigger): 
                            $trigger_id = $trigger->getId();
                            $trigger_settings = $settings[$trigger_id] ?? [];
                            $webhook_url = $trigger_settings['webhook_url'] ?? '';
                            $notes = $trigger_settings['notes'] ?? '';
                            $enabled = $trigger_settings['enabled'] ?? false;
                        ?>
                            <tr>
                                <td style="width:20%">
                                    <strong><?php echo esc_html($trigger->getName()); ?></strong>
                                    <br>
                                    <small class="description"><?php echo esc_html($trigger->getDescription()); ?></small>
                                </td>
                                <td style="width:10%; text-align:center;">
                                    <label>
                                        <input type="checkbox" 
                                               name="drilion_trigger_settings[<?php echo esc_attr($trigger_id); ?>][enabled]" 
                                               value="1" 
                                               <?php checked($enabled, true); ?>>
                                    </label>
                                </td>
                                <td style="width:45%">
                                    <input type="url" 
                                           name="drilion_trigger_settings[<?php echo esc_attr($trigger_id); ?>][webhook_url]" 
                                           value="<?php echo esc_attr($webhook_url); ?>" 
                                           class="webhook-url-input"
                                           placeholder="https://">
                                </td>
                                <td style="width:25%">
                                    <textarea name="drilion_trigger_settings[<?php echo esc_attr($trigger_id); ?>][notes]" 
                                              rows="2" 
                                              style="width: 100%;"><?php echo esc_textarea($notes); ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}