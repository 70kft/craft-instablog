<?php

namespace Craft;

class InstaBlog_ImportBackupTask extends BaseTask
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
    return Craft::t('InstaBlog Database Backup');
  }


  /**
   * Gets the total number of steps for this task.
   *
   * @return int
   */
  public function getTotalSteps()
  {
    return 1;
  }


  /**
   * Runs a task step.
   *
   * @param int $step
   * @return bool
   */
  public function runStep($step)
  {

    $return = craft()->updates->backupDatabase();

    if (!$return['success'])
    {
      return $return['message'];
    }

    return true;
  }
}