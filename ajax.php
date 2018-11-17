<?php
/*
*  @author nguyenhongphat0 <nguyenhongphat28121998@gmail.com>
*  @license https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
*/

include_once('../../config/config.inc.php');
include_once('../../init.php');

/**
 * Handle all ajax calls in module
 */
class DeveloperPackAjax
{
    public function __construct()
    {
        // Check if module is enable and user have permission
        $cookie = new Cookie('psAdmin');
        $is_admin = $cookie->id_employee;
        $is_enabled = Module::isEnabled('developerpack');
        if (!$is_admin || !$is_enabled) {
            $this->end('Access dinied', 403);
        }
        // Call to action
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
        } else {
            $action = $_POST['action'];
        }
        if (method_exists($this, $action)) {
            $this->$action();
        } else {
            $this->end('Action not found', 404);
        }
    }

    public function end($data, $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        die(json_encode($data));
    }

    public function testing()
    {
        $result = Configuration;
        $this->end($result);
    }
}

new DeveloperPackAjax();
