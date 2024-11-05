<?php

namespace theme_moove\api;

global $CFG;
require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/config.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use theme_moove\util\savy;

/**
 * Launch savy by *securely* creating a chat in the backend using the user's information.
 * This avoids sending unencrypted data to the front-end.
 *
 * @package theme_moove
 */
class launch_savy extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function launch_savy_parameters()
    {
        return new external_function_parameters([
            'anonymous' => new external_value(PARAM_BOOL, 'Whether to launch anonymously (for testing)', VALUE_DEFAULT, false)
        ]);
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function launch_savy_returns()
    {
        return new external_single_structure([
            'script' => new external_value(PARAM_RAW, 'The embed payload to launch Cria', true),
            'botId' => new external_value(PARAM_TEXT, 'The bot ID', true),
            'stack' => new external_value(PARAM_TEXT, 'Fail Reason', false),
        ]);
    }

    /**
     * The actual implementation of the API method
     * @return array
     */
    public static function launch_savy($anonymous = false)
    {
        global $DB, $USER;

        // Get the payload
        try {
            $payload = savy::get_savy_payload($anonymous);
        } catch (\Exception $e) {
            return [
                'script' => "console.error('Retrieving savy payload failed!')",
                'stack' => $e->getMessage(),
                'botId' => ''
            ];
        }

        try {
            $savy = savy::exec_embed($payload);
        } catch (\Exception $e) {
            return [
                'script' => "console.error('Creating savy chat failed!')",
                'stack' => $e->getMessage(),
                'botId' => ''
            ];
        }

        return [
            'script' => $savy,
            'botId' => get_config('theme_moove', 'savy_bot_id')
        ];
    }
}
