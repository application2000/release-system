<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();

jimport('joomla.application.component.model');

/**
 * The Live Update MVC model
 */
class LiveUpdateModel extends JModel
{
	public function download()
	{
		// Get the path to Joomla!'s temporary directory
		$jreg =& JFactory::getConfig();
		$tmpdir = $jreg->getValue('config.tmp_path');
		
		jimport('joomla.filesystem.folder');
		// Make sure the user doesn't use the system-wide tmp directory. You know, the one that's
		// being erased periodically and will cause a real mess while installing extensions (Grrr!)
		if(realpath($tmpdir) == '/tmp') {
			// Someone inform the user that what he's doing is insecure and stupid, please. In the
			// meantime, I will fix what is broken.
			$tmpdir = JPATH_SITE.DS.'tmp';
		} // Make sure that folder exists (users do stupid things too often; you'd be surprised)
		elseif(!JFolder::exists($tmpdir)) {
			// Darn it, user! WTF where you thinking? OK, let's use a directory I know it's there...
			$tmpdir = JPATH_SITE.DS.'tmp';
		}

		// Oki. Let's get the URL of the package
		$updateInfo = LiveUpdate::getUpdateInformation();
		$url = $updateInfo->downloadURL;
		$config = LiveUpdateConfig::getInstance();
		$auth = $config->getAuthorization();
		
		// Sniff the package type. If sniffing is impossible, I'll assume a ZIP package
		$basename = basename($url);
		if(strstr($basename,'?')) {
			$basename = substr($basename, strstr($basename,'?')+1);
		}
		if(substr($basename,-4) == '.zip') {
			$type = 'zip';
		} elseif(substr($basename,-4) == '.tar') {
			$type = 'tar';
		} elseif(substr($basename,-4) == '.tgz') {
			$type = 'tar.gz';
		} elseif(substr($basename,-7) == '.tar.gz') {
			$type = 'tar.gz';
		} else {
			$type = 'zip';
		}
		
		// Cache the path to the package file and the temp installation directory in the session
		$target = $tmpdir.DS.$updateInfo->extInfo->name.'.update.'.$type;
		$tempdir = $tmpdir.DS.$updateInfo->extInfo->name.'_update';
		
		$session = JFactory::getSession();
		$session->set('target', $target, 'liveupdate');
		$session->set('tempdir', $tempdir, 'liveupdate');
		
		// Let's download!
		require_once dirname(__FILE__).'/download.php';
		$url .= $auth;
		return LiveUpdateDownloadHelper::download($url, $target);
	}
	
	public function extract()
	{
		$session = JFactory::getSession();
		$target = $session->get('target', '', 'liveupdate');
		$tempdir = $session->get('tempdir', '', 'liveupdate');
		
		jimport('joomla.filesystem.archive');
		return JArchive::extract( $target, $tempdir);
	}
	
	public function install()
	{
		$session = JFactory::getSession();
		$tempdir = $session->get('tempdir', '', 'liveupdate');

		jimport('joomla.installer.installer');
		jimport('joomla.installer.helper');
		$installer =& JInstaller::getInstance();
		$packageType = JInstallerHelper::detectType($tempdir);
		
		if(!$packageType) {
			$msg = JText::_('LIVEUPDATE_INVALID_PACKAGE_TYPE');
			$result = false;
		} elseif (!$installer->install($tempdir)) {
			// There was an error installing the package
			$msg = JText::sprintf('LIVEUPDATE_INSTALLEXT', JText::_($packageType), JText::_('LIVEUPDATE_Error'));
			$result = false;
		} else {
			// Package installed sucessfully
			$msg = JText::sprintf('LIVEUPDATE_INSTALLEXT', JText::_($packageType), JText::_('LIVEUPDATE_Success'));
			$result = true;
		}
		
		$app = JFactory::getApplication();
		$app->enqueueMessage($msg);
		$this->setState('result', $result);
		$this->setState('packageType', $packageType);
		if($packageType) {
			$this->setState('name', $installer->get('name'));
			$this->setState('message', $installer->message);
			if(version_compare(JVERSION,'1.6.0','ge')) {
				$this->setState('extmessage', $installer->get('extension_message'));
			} else {
				$this->setState('extmessage', $installer->get('extension.message'));
			}
		}
		
		return $result;
	}
	
	public function cleanup()
	{
		$session = JFactory::getSession();
		$target = $session->get('target', '', 'liveupdate');
		$tempdir = $session->get('tempdir', '', 'liveupdate');
		
		jimport('joomla.installer.helper');
		JInstallerHelper::cleanupInstall($target, $tempdir);
		
		$session->clear('target','liveupdate');
		$session->clear('tempdir','liveupdate');
	}
}