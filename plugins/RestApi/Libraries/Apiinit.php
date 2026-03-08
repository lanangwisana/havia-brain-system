<?php
namespace RestApi\Libraries;

require_once __DIR__ .'/../ThirdParty/node.php';

use \WpOrg\Requests\Requests as Requests;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

\WpOrg\Requests\Autoload::register();

class Apiinit {
    public static function check_url($module_name) {
        return true;
    }
}
