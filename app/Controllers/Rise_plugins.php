<?php

namespace App\Controllers;

class Rise_plugins extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
    }

    function index() {
        return $this->template->rander("plugins/index");
    }

    function modal_form() {
        return $this->template->view('plugins/modal_form');
    }

    function list_data() {
        $activated_plugins = array();
        $activated_plugins_json = APPPATH . 'Config/activated_plugins.json';
        if (is_file($activated_plugins_json)) {
            $activated_plugins = json_decode(file_get_contents($activated_plugins_json), true);
        }

        $plugins = array();
        if (is_dir(PLUGINPATH)) {
            $dh = opendir(PLUGINPATH);
            while (($file = readdir($dh)) !== false) {
                if ($file != "." && $file != ".." && is_dir(PLUGINPATH . $file)) {
                    $plugin_info = $this->_get_plugin_info($file);
                    if ($plugin_info) {
                        $plugins[] = array(
                            "id" => $file,
                            "plugin_info" => $plugin_info,
                            "status" => in_array($file, $activated_plugins) ? "activated" : "deactivated"
                        );
                    }
                }
            }
            closedir($dh);
        }

        $result = array();
        foreach ($plugins as $plugin) {
            $result[] = $this->_make_row($plugin["id"], $plugin["status"], $plugin["plugin_info"]);
        }

        echo json_encode(array("data" => $result));
    }

    private function _make_row($plugin, $status, $plugin_info) {
        $status_class = ($status === "activated") ? "bg-success" : "bg-danger";
        $action_label = ($status === "activated") ? "Deactivate" : "Activate";
        $action_type = ($status === "activated") ? "deactivated" : "activated";
        $icon = ($status === "activated") ? "pause" : "play";

        // Tombol Aksi (Activate/Deactivate)
        $action = '<li class="dropdown-item">' . ajax_anchor(get_uri("rise_plugins/save_status_of_plugin/$plugin/$action_type/1"), "<i data-feather='$icon' class='icon-16'></i> " . $action_label, array("data-reload-on-success" => true)) . '</li>';

        // Tombol Delete
        $delete = '<li class="dropdown-item">' . js_anchor("<i data-feather='x' class='icon-16'></i> Delete", array("data-action-url" => get_uri("rise_plugins/delete/$plugin"), "data-action" => "delete-confirmation", "data-reload-on-success" => true)) . '</li>';

        $option = '
                <span class="dropdown inline-block">
                    <button class="btn btn-default dropdown-toggle caret mt0 mb0" type="button" data-bs-toggle="dropdown">
                        <i data-feather="tool" class="icon-16"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">' . $action . $delete . '</ul>
                </span>';

        return array(
            "<b>" . get_array_value($plugin_info, "Plugin Name") . "</b><br /><small>Version " . get_array_value($plugin_info, "Version") . "</small>",
            get_array_value($plugin_info, "Description"),
            "<span class='badge $status_class'>$status</span>",
            $option
        );
    }

    // Fungsi untuk memproses Aktivasi/Deaktivasi
    function save_status_of_plugin($plugin_name = "", $status = "", $echo_json = false) {
        $activated_plugins_json = APPPATH . 'Config/activated_plugins.json';
        $activated_plugins = array();
        if (is_file($activated_plugins_json)) {
            $activated_plugins = json_decode(file_get_contents($activated_plugins_json), true);
        }

        if ($status === "activated") {
            if (!in_array($plugin_name, $activated_plugins)) {
                $activated_plugins[] = $plugin_name;
            }
        } else {
            if (($key = array_search($plugin_name, $activated_plugins)) !== false) {
                unset($activated_plugins[$key]);
            }
        }

        file_put_contents($activated_plugins_json, json_encode(array_values($activated_plugins)));

        if ($echo_json) {
            echo json_encode(array("success" => true));
        }
    }

    // Fungsi untuk menghapus plugin
    function delete($plugin_name = "") {
        if ($plugin_name) {
            // Kita hanya hapus dari list aktivasi dulu demi keamanan file fisik
            $this->save_status_of_plugin($plugin_name, "deactivated");
            echo json_encode(array("success" => true, 'message' => 'Plugin deactivated and removed from list.'));
        }
    }

    private function _get_plugin_info($plugin_name) {
        $plugin_file = PLUGINPATH . $plugin_name . "/index.php";
        if (is_file($plugin_file)) {
            $contents = @file_get_contents($plugin_file);
            preg_match('/Plugin Name:(.*)$/mi', $contents, $name);
            preg_match('/Description:(.*)$/mi', $contents, $description);
            preg_match('/Version:(.*)$/mi', $contents, $version);
            return array(
                "Plugin Name" => trim(get_array_value($name, 1)),
                "Description" => trim(get_array_value($description, 1)),
                "Version" => trim(get_array_value($version, 1))
            );
        }
        return false;
    }
}