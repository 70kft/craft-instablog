<?php

namespace Craft;

class InstaBlog_ImportWordpressTask extends BaseTask
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
    return Craft::t('InstaBlog WP Import');
  }


  /**
   * Return total steps.
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
   * Run step.
   *
   * @param int $step
   *
   * @return bool
   */
  public function runStep($step)
  {
    // Get settings
    $settings = $this->getSettings();

    if (!craft()->instaBlog_import->importData($step, $settings))
    {
      return 'InstaBlog Wordpress import step '.$step.' failed.';
    }

    return true;
    
  }
}