<?php 
namespace Craft;

class InstaBlog_UninstallService extends BaseApplicationComponent
{

  function __construct()
  {
    craft()->config->set('devMode', false);
    craft()->config->maxPowerCaptain();
  }


  // Public Methods
  // =========================================================================

  /**
   * Installs InstaBlog!
   *
   * @return null
   */
  public function run()
  {

    $this->_removeBlogContent();
    $this->_deleteTemplates();
    Craft::log('Finished uninstalling InstaBlog.', LogLevel::Info, true, 'application', 'InstaBlog');
    return true;
  }

  /**
   * Creates initial database content for InstaBlog.
   *
   * @return null
   */
  private function _removeBlogContent()
  {
    // Remove InstaBlog field group
    Craft::log('Removing all InstaBlog fields.');
    $groups = craft()->fields->getAllGroups();
    $groupsByName = array();
    
    foreach ($groups as $group) {
      $groupsByName[$group["name"]] = $group["id"];
    }

    if (array_key_exists('InstaBlog', $groupsByName))
    {
      if (craft()->fields->deleteGroupById($groupsByName["InstaBlog"]))
      {
        Craft::log('InstaBlog field group deleted successfully.');
      }
      else
      {
        Craft::log('Could not delete the InstaBlog field group.', LogLevel::Error, true, 'application', 'InstaBlog');
      }
    }


    // Remove InstaBlog Category Group
    Craft::log('Removing all InstaBlog categories.');    
    
    if ($instaBlogCategories = craft()->categories->getGroupByHandle('instaBlogCategories'))
    {
      if (craft()->categories->deleteGroupById($instaBlogCategories["id"]))
      {
        Craft::log('InstaBlog category group deleted successfully.');
      }
      else
      {
        Craft::log('Could not delete the InstaBlog category group.', LogLevel::Error, true, 'application', 'InstaBlog');
      }      
    }



    // Remove InstaBlog Tag Group
    Craft::log('Removing all InstaBlog tags.');
    
    if ($instaBlogTags = craft()->tags->getTagGroupByHandle('instaBlogTags'))
    {
      if (craft()->tags->deleteTagGroupById($instaBlogTags["id"]))
      {
        Craft::log('InstaBlog tag group deleted successfully.');
      }
      else
      {
        Craft::log('Could not delete the InstaBlog tag group.', LogLevel::Error, true, 'application', 'InstaBlog');
      }
    }


    // Remove InstaBlog Section
    Craft::log('Removing the InstaBlog Section.');

    if ($instaBlogSection = craft()->sections->getSectionByHandle('InstaBlog'))
    {
      if (craft()->sections->deleteSectionById($instaBlogSection["id"]))
      {
        Craft::log('InstaBlog section deleted successfully.');
      }
      else
      {
        Craft::log('Could not delete the InstaBlog section.', LogLevel::Error, true, 'application', 'InstaBlog');
      }      
    }


    // Remove InstaBlog Routes
    Craft::log('Removing InstaBlog Routes');

    craft()->db->createCommand()->delete('routes', 
      array('template' => 'blog/tag'));

    craft()->db->createCommand()->delete('routes', 
      array('template' => 'blog/author'));
  }



  /**
   * Deletes template files from templates folder
   *
   * @return null
   */
  private function _deleteTemplates()
  {
    $craftTemplateFolder = realpath(CRAFT_TEMPLATES_PATH);
    $instaBlogTargetFolder = $craftTemplateFolder.'/blog';

    // Try nicely to delete files
    IOHelper::deleteFolder($instaBlogTargetFolder, true);

    // If folder remains try to force it.
    if (is_dir($instaBlogTargetFolder))
    {
      @system('/bin/rm -rf ' . escapeshellarg($instaBlogTargetFolder));
    }
  }
}


?>