<?php
if (!defined('ABSPATH')) {
    exit;
}

class CNNTurk_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $config;
    private $github_data;
    private $access_token;

    public function __construct($file, $config = array()) {
        $this->file = $file;
        $this->config = wp_parse_args($config, array(
            'slug' => plugin_basename($file),
            'proper_folder_name' => dirname(plugin_basename($file)),
            'api_url' => 'https://api.github.com/repos/owner/repo/releases',
            'raw_url' => 'https://raw.githubusercontent.com/owner/repo/main',
            'github_url' => 'https://github.com/owner/repo',
            'zip_url' => 'https://api.github.com/repos/owner/repo/zipball',
            'requires' => '5.0',
            'tested' => '6.4',
            'readme' => 'README.md'
        ));

        $this->basename = plugin_basename($file);
        $this->active = is_plugin_active($this->basename);

        // WordPress güncelleme kancaları
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'get_plugin_info'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'source_selection'), 10, 4);
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_github_data();

        if ($remote_version && version_compare(CNNTURK_BOT_VERSION, $remote_version->tag_name, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->config['slug'];
            $obj->new_version = $remote_version->tag_name;
            $obj->url = $this->config['github_url'];
            $obj->package = $remote_version->zipball_url;
            $obj->tested = $this->config['tested'];
            $obj->requires = $this->config['requires'];
            $obj->last_updated = $remote_version->published_at;
            
            $transient->response[$this->basename] = $obj;
        }

        return $transient;
    }

    public function get_plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information') {
            return $false;
        }

        if (!isset($response->slug) || $response->slug !== $this->config['slug']) {
            return $false;
        }

        $remote_version = $this->get_github_data();

        $response = new stdClass();
        $response->name = 'CNN Türk Haber Botu';
        $response->slug = $this->config['slug'];
        $response->version = $remote_version->tag_name;
        $response->author = 'Senin İsmin';
        $response->homepage = $this->config['github_url'];
        $response->requires = $this->config['requires'];
        $response->tested = $this->config['tested'];
        $response->downloaded = 0;
        $response->last_updated = $remote_version->published_at;
        $response->sections = array(
            'description' => $remote_version->body,
            'changelog' => $this->get_changelog()
        );
        $response->download_link = $remote_version->zipball_url;

        return $response;
    }

    private function get_github_data() {
        if (!empty($this->github_data)) {
            return $this->github_data;
        }

        $github_data = get_transient('cnnturk_bot_github_data');

        if (!$github_data) {
            $response = wp_remote_get($this->config['api_url'], array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json'
                )
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $data = json_decode(wp_remote_retrieve_body($response));

            if (is_array($data)) {
                $data = current($data);
            }

            set_transient('cnnturk_bot_github_data', $data, 60 * 60);
        }

        $this->github_data = $data;
        return $this->github_data;
    }

    private function get_changelog() {
        $response = wp_remote_get($this->config['raw_url'] . '/CHANGELOG.md');

        if (is_wp_error($response)) {
            return 'Değişiklik geçmişi yüklenemedi.';
        }

        return wp_remote_retrieve_body($response);
    }

    public function source_selection($source, $remote_source, $upgrader, $hook_extra = null) {
        global $wp_filesystem;
        
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->basename) {
            return $source;
        }

        $path = trailingslashit($remote_source);
        $files = $wp_filesystem->dirlist($path);

        if (count($files) === 1) {
            $first_file = current($files);
            if ($first_file['type'] === 'd') {
                $from_path = trailingslashit($path . $first_file['name']);
                $to_path = trailingslashit($path . $this->config['proper_folder_name']);

                if ($from_path !== $to_path) {
                    return rename($from_path, $to_path) ? $to_path : $source;
                }
            }
        }

        return $source;
    }
} 