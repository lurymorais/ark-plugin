<?php

/**
 * @file plugins/pubIds/ark/classes/jobs/SendArkStatisticsPushTask.php
 *
 * @brief Scheduled task to send anonymous statistics to the telemetry server
 * 
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 */

use PKP\scheduledTask\ScheduledTask;
use APP\core\Application;
use Illuminate\Support\Facades\DB;

class SendArkStatisticsPushTask extends ScheduledTask
{
    /** @var ARKPubIdPlugin */
    private $plugin;
    
    /**
     * Constructor
     * 
     * @param array $args Task arguments
     */
    public function __construct($args)
    {
        parent::__construct($args);
        
        // Get plugin instance
        $this->plugin = \PKP\plugins\PluginRegistry::getPlugin('pubIds', 'ark');
        
        if (!$this->plugin) {
            error_log("[ARK] Plugin not found in scheduled task");
        }
    }
    
    /**
     * Execute the scheduled task actions
     * 
     * @return bool True if execution was successful
     */
    protected function executeActions()
    {
        if (!$this->plugin) {
            error_log("[ARK] Cannot execute scheduled task: plugin not found");
            return false;
        }
        
        $success = true;
        $sentCount = 0;
        $errorCount = 0;
        
        try {
            // Get all active journals
            $contextDao = Application::getContextDAO();
            $journals = $contextDao->getAll();
            
            while ($journal = $journals->next()) {
                $contextId = $journal->getId();
                
                // Skip if telemetry is disabled (opt-out)
                $telemetryEnabled = $this->plugin->getSetting($contextId, 'telemetryEnabled');
                
                // Default is enabled (true) if not set
                if ($telemetryEnabled === '0') {
                    continue;
                }
                
                // Get NAAN
                $naan = $this->plugin->getSetting($contextId, 'arkPrefix');
                if (empty($naan)) {
                    continue;
                }
                
                // Send statistics
                $result = $this->plugin->sendStatistics($contextId);
                
                if ($result) {
                    $sentCount++;
                    error_log("[ARK] Statistics sent for journal {$contextId} ({$naan})");
                } else {
                    $errorCount++;
                    error_log("[ARK] Failed to send statistics for journal {$contextId} ({$naan})");
                    $success = false;
                }
            }
            
            error_log("[ARK] Scheduled task completed: {$sentCount} sent, {$errorCount} errors");
            
        } catch (Exception $e) {
            error_log("[ARK] Error in scheduled task: " . $e->getMessage());
            return false;
        }
        
        return $success;
    }
}