<?php

namespace Craft;

class InstaBlog_ImportController extends BaseController
{

    /**
     * Upload file and process it for mapping.
     */
    public function actionConfirm()
    {

      $this->requirePostRequest();
      craft()->userSession->requireAdmin();

      // Get POST fields
      $import = craft()->request->getRequiredPost('import');

      // Get file
      $file = \CUploadedFile::getInstanceByName('file');

      // Is file valid?
      if (!is_null($file))
      {
        // Determine folder
        $folder = craft()->path->getStoragePath().'instablog/';

        // Ensure folder exists
        IOHelper::ensureFolderExists($folder);

        // Get filepath - save in storage folder
        $path = $folder.$file->getName();

        // Save file to Craft's temp folder for later use
        if ($file->saveAs($path))
        {
          // Put vars in model and validate filetype 
          $model           = new InstaBlog_ImportModel();
          $model->filetype = $file->getType();

          if ($model->validate())
          {

            $variables = craft()->instaBlog_import->prepData($path);
            $variables['import'] = $import;
            $variables['file']   = $path;

            // Send variables to template and display
            $this->renderTemplate('instablog/settings/_confirm', $variables);
          }
          else
          {
            // Not validated, delete and show error
            @unlink( $path );
            craft()->userSession->setError(Craft::t('This filetype is not valid. Expected XML but got') . ': ' .$model->filetype);
          }
        }
        else
        {
          // No file uploaded probably due to php settings.
          craft()->userSession->setError(Craft::t('Couldn\'t Upload file. Check upload_max_filesize or other php.ini settings.'));
        }
      }
      else
      {
        // No file uploaded
        craft()->userSession->setError(Craft::t('Select a Wordpress XML export file to upload.'));
      }
    }


    /**
     * Start import task.
     */
    public function actionStart()
    {

      $this->requirePostRequest();
      craft()->userSession->requireAdmin();

      // Get posts
      $file             = craft()->request->getParam('file');
      $backup           = craft()->request->getParam('backup');
      $assetDestination = craft()->request->getParam('assetDestination');
      $import           = craft()->request->getParam('import');

      // Set more settings
      $settings = array(
          'file'              => $file,
          'backup'            => $backup,
          'assetDestination'  => $assetDestination,
          'import'            => $import
      );

      // If backup requested, run backup task
      if ($backup)
      {
        craft()->tasks->createTask('InstaBlog_ImportBackup', null, $settings); 
      }

      // Create the import task
      if ($task = craft()->tasks->createTask('InstaBlog_ImportWordpress', null, $settings))
      {
        // Create link update task
        craft()->tasks->createTask('InstaBlog_ImportWordpressUpdateLinks', null, $settings); 
        // Notify user
        craft()->userSession->setNotice(Craft::t('Import process started.'));

        // Redirect 
        $this->redirect('instablog/settings/import?task='.$task->id);
      }
      else
      {

        // Warn User
        craft()->userSession->setError(Craft::t('Import failed.'));

        // Redirect
        $this->redirect('instablog/settings/import');
      }
    }
  }