<?php
namespace Craft;
class InstaBlogVariable {
  /**
   * @var bool
   */
  public $enabled;
  
  /**
   * @var array
   */
  public $settings;
  

  /**
   * Assigns some plugin properties to properties.
   */
  public function __construct() {
    $this->settings = craft()->plugins->getPlugin('InstaBlog')->getSettings();
  }


  /**
   * Returns the attributes of the plugin's settings model
   *
   * @return array
   */
  public function settings() {
    return $this->settings->attributes;
  }

  public function getSources() {

    $sources = craft()->assetSources->getAllSources();
    $response = array();

    foreach ($sources as $source)
    {
      $response[$source["id"]] = $source["name"];
    }
    return $response;
  }

}
