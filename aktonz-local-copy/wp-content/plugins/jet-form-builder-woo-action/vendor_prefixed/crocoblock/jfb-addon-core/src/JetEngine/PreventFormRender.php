<?php

namespace JFB\WooComm\Vendor\JFBCore\JetEngine;

use JFB\WooComm\Vendor\JFBCore\PreventRenderFormBase;
abstract class PreventFormRender
{
    use PreventRenderFormBase;
    public function form_id_key()
    {
        return '_form_id';
    }
    public function action_name()
    {
        return 'jet-engine/forms/pre-render-form';
    }
}
