<?php

namespace JFB\WooComm\Vendor\JFBCore\JetFormBuilder;

use JFB\WooComm\Vendor\JFBCore\RegisterMetaManager;
abstract class PluginManager
{
    use EditorAssetsManager;
    use RegisterMetaManager;
    use WithInit;
    public function on_plugin_init()
    {
        $this->meta_manager_init();
        $this->assets_init();
    }
}
