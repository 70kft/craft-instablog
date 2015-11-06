<?php
namespace Craft;

class InstaBlogPlugin extends BasePlugin
{
  public function init()
  {
    Craft::import('plugins.instablog.lib.wp-parser', true);
  }

  public function getName()
  {
    return Craft::t('InstaBlog');
  }


  public function getVersion()
  {
    return '1.0';
  }


  public function getDeveloper()
  {
    return '70kft';
  }


  public function getDeveloperUrl()
  {
    return 'http://70kft.com';
  }


  public function getSettingsUrl()
  {
    return UrlHelper::getUrl('/instablog/settings');
  }


  protected function defineSettings()
  {
    return array(
        'layout'  => array(AttributeType::String, 'required' => false),
        'googlePlus'  => array(AttributeType::String, 'required' => false),
        'twitter'    => array(AttributeType::String, 'required' => false),
        'facebook'    => array(AttributeType::String, 'required' => false),
        'linkedin'    => array(AttributeType::String, 'required' => false),
        'disqus'    => array(AttributeType::String, 'required' => false)
      );
  }


  public function getSettingsHtml()
  {
    return craft()->templates->render('instablog/settings', array(
      'settings' => $this->getSettings()
    ));
  }


  public function addTwigExtension()
  {
    Craft::import('plugins.instablog.twigextensions.InstaBlogTruncateTwigExtension');
    return new InstaBlogTruncateTwigExtension();
  }
  

  public function onBeforeInstall()
  {
    if (!((craft()->getEdition() > 0) || craft()->sections->canHaveMore("channel")))
    {
      throw new Exception(Craft::t('Your version of Craft only permits one channel section. '
        . 'Please upgrade or remove the existing channel section before installing InstaBlog.'));
    }

    $craftTemplateFolder = realpath(CRAFT_TEMPLATES_PATH);

    if ((!IOHelper::isWritable($craftTemplateFolder)))
    {
      throw new Exception(Craft::t('Your Template folder is not writeable by PHP. '
        . 'InstaBlog needs PHP to have permissions to create template files. Give PHP write permissions to '
        . $craftTemplateFolder . ' and try Install again.'));
    }

    $sources = craft()->assetSources->getAllSourceIds();

    if (empty($sources))
    {
      throw new Exception(Craft::t('You don\'t have any asset sources set up. '
        . 'InstaBlog needs an asset source to be defined. Please create an asset source '
        . ' and try Install again.'));
    }
  }


  public function onAfterInstall()
  {
    craft()->instaBlog_install->run();
  }


  public function onBeforeUninstall()
  {
    craft()->instaBlog_uninstall->run();
  }

}