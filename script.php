<?php
// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class plgSystemOpengraphInstallerScript
{

    public function install($parent)
    {
        // Enable the extension
        $this->enablePlugin();

        return true;
    }

    private function enablePlugin()
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->update($db->qn('#__extensions'))
                ->set($db->qn('enabled') . ' = ' . $db->q(1))
                ->where('type = ' . $db->q('plugin'))
                ->where('folder = ' . $db->q('system'))
                ->where('element = ' . $db->q('opengraph'));
            $db->setQuery($query);
            $db->execute();
        } catch (Exception $e) {
            return;
        }
    }
}
