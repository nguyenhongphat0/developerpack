<?php
/**
 *  @author nguyenhongphat0 <nguyenhongphat28121998@gmail.com>
 *  @copyright 2018 nguyenhongphat0
 *  @license https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Module main class
 */
class DeveloperPack extends Module
{
    public function __construct()
    {
        $this->name = 'developerpack';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'nguyenhongphat0';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_
        );
        $this->bootstrap = true;
        $this->module_key = '26d140b284bb3ecd72f3f0f09131ab45';
        parent::__construct();

        $this->displayName = $this->l('Developer pack');
        $this->description = $this->l('This modules contain everything a prestashop developer need.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('foo')) {
            $this->warning = $this->l('No foo provided');
        }
    }

    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit'.$this->name)) {
            $myModuleName = Tools::getValue('foo');

            if (!$myModuleName ||
                empty($myModuleName) ||
                !Validate::isGenericName($myModuleName)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('foo', $myModuleName);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayConfiguration();
    }

    public function displayConfiguration()
    {
        $this->context->smarty->assign(array(
            'foo' => Configuration::get('foo'),
            'root' => __PS_BASE_URI__,
            'version' => _PS_VERSION_
        ));
        return $this->display(__FILE__, 'views/templates/admin/index.tpl');
    }
}
