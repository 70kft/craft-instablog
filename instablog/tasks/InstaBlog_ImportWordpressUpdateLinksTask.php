<?php

namespace Craft;

class InstaBlog_ImportWordpressUpdateLinksTask extends BaseTask
{

  /**
   * Define settings.
   *
   * @return array
   */
  protected function defineSettings()
  {
      return array(
          'file'              => AttributeType::Name,
          'backup'            => array(AttributeType::Bool, 'default' => false),
          'assetDestination'  => AttributeType::Number,
          'import'            => array(AttributeType::Mixed, 'default' => array()),
      );
  }


  /**
   * Return description.
   *
   * @return string
   */
  public function getDescription()
  {
    return Craft::t('InstaBlog WP Import Update Links');
  }


  /**
   * Gets the total number of steps for this task.
   *
   * @return int
   */
  public function getTotalSteps()
  {
    // Get settings
    $settings = $this->getSettings();
    // Take a step for every post + author
    $count = count($settings->import);

    return $count;
  }


  /**
   * Runs a task step.
   *
   * @param int $step
   * @return bool
   */
  public function runStep($step)
  {
    // Get settings
    $settings = $this->getSettings();

    if (craft()->instaBlog_import->updateLinks($step, $settings))
    {
      return true;

    }
    else
    {
      
      return 'InstaBlog Wordpress update links step '.$step.' failed.';
    
    }
  }
}