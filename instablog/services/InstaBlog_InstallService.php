<?php
namespace Craft;

class InstaBlog_InstallService extends BaseApplicationComponent
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

    $primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
    $error = null;

    try
    {
      $this->_createInstaBlogContent($primaryLocaleId);
      $this->_copyTemplates();
    }
    catch(\Exception $e)
    {
      $error = 'An exception was thrown: '.$e->getMessage();
    }

    if ($error === null)
    {
      Craft::log('Finished installing InstaBlog',
       LogLevel::Info, true, 'application', 'InstaBlog');
      return true;
    }
    else
    {
      Craft::log('Failed installing InstaBlog: ' . $error,
       LogLevel::Error, true, 'application', 'InstaBlog');
      return false;
    }
  }


  /**
   * Creates initial database content for InstaBlog.
   *
   * @return null
   */
  private function _createInstaBlogContent()
  {
    // InstaBlog tag group
    Craft::log('Creating the InstaBlog tag group.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $tagGroup = new TagGroupModel();
    $tagGroup->name   = 'InstaBlog Tags';
    $tagGroup->handle = 'instaBlogTags';

    // Save it
    if (craft()->tags->saveTagGroup($tagGroup))
    {
      Craft::log('InstaBlog tag group created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog tag group.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // InstaBlog field group
    Craft::log('Creating the InstaBlog field group.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $group = new FieldGroupModel();
    $group->name = 'InstaBlog';

    if (craft()->fields->saveGroup($group))
    {
      Craft::log('InstaBlog field group created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog field group.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Body field
    Craft::log('Creating the InstaBlog Body field.');

    $bodyField = new FieldModel();
    $bodyField->groupId      = $group->id;
    $bodyField->name         = 'InstaBlog Body';
    $bodyField->handle       = 'instaBlogBody';
    $bodyField->translatable = true;
    $bodyField->type         = 'RichText';
    $bodyField->settings = array(
      'configFile' => 'Standard.json',
      'columnType' => ColumnType::Text,
    );

    if (craft()->fields->saveField($bodyField))
    {
      Craft::log('InstaBlog Body field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog Body field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Facebook field
    Craft::log('Creating the InstaBlog Facebook field.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $facebookField = new FieldModel();
    $facebookField->groupId      = $group->id;
    $facebookField->name         = 'Facebook';
    $facebookField->handle       = 'instaBlogFacebook';
    $facebookField->translatable = false;
    $facebookField->type         = 'PlainText';
    $facebookField->instructions = 'Add your personal Facebook profile link. Example: https://www.facebook.com/xxxxxxxxxx';

    if (craft()->fields->saveField($facebookField))
    {
      Craft::log('Facebook field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the Facebook field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Twitter Handle field
    Craft::log('Creating the InstaBlog Twitter field.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $twitterField = new FieldModel();
    $twitterField->groupId      = $group->id;
    $twitterField->name         = 'Twitter';
    $twitterField->handle       = 'instaBlogTwitter';
    $twitterField->translatable = false;
    $twitterField->type         = 'PlainText';
    $twitterField->instructions = 'Add your personal Twitter handle. Example: @johndoe';

    if (craft()->fields->saveField($twitterField))
    {
      Craft::log('Twitter field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the Twitter field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Google+ field
    Craft::log('Creating the InstaBlog Google+ field.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $googlePlusField = new FieldModel();
    $googlePlusField->groupId      = $group->id;
    $googlePlusField->name         = 'Google+';
    $googlePlusField->handle       = 'instaBlogGooglePlus';
    $googlePlusField->translatable = false;
    $googlePlusField->type         = 'PlainText';
    $googlePlusField->instructions = 'Add your personal Google+ profile link. Example: https://plus.google.com/+JohnDoe';

    if (craft()->fields->saveField($googlePlusField))
    {
      Craft::log('Google+ field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the Google+ field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // LinkedIn field
    Craft::log('Creating the InstaBlog LinkedIn field.');

    $linkedinField = new FieldModel();
    $linkedinField->groupId      = $group->id;
    $linkedinField->name         = 'LinkedIn';
    $linkedinField->handle       = 'instaBlogLinkedin';
    $linkedinField->translatable = false;
    $linkedinField->type         = 'PlainText';
    $linkedinField->instructions = 'Add your personal LinkedIn profile link. Example: https://www.linkedin.com/pub/john-doe/3/7aa/91b';

    if (craft()->fields->saveField($linkedinField))
    {
      Craft::log('LinkedIn field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the LinkedIn field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Create the new user field layout
    Craft::log('Creating the new user profile layout.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $userFieldLayout = craft()->fields->getLayoutByType(ElementType::User);
    $fieldsIds = $userFieldLayout->getFieldIds();

    $fieldsIds[] = $facebookField->id;
    $fieldsIds[] = $twitterField->id;
    $fieldsIds[] = $googlePlusField->id;
    $fieldsIds[] = $linkedinField->id;

    craft()->fields->deleteLayoutsByType(ElementType::User);

    $userFieldLayout = craft()->fields->assembleLayout(
        array(
            Craft::t('Profile') => $fieldsIds,
        ),
        array(),
        false
    );
    $userFieldLayout->type = ElementType::User;

    if (craft()->fields->saveLayout($userFieldLayout, false))
    {
      Craft::log('User profile layout saved successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the user profile layout.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Tags field
    Craft::log('Creating the Tags field.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $tagsField = new FieldModel();
    $tagsField->groupId      = $group->id;
    $tagsField->name         = 'InstaBlog Tags';
    $tagsField->handle       = 'instaBlogTags';
    $tagsField->type         = 'Tags';
    $tagsField->settings = array(
      'source' => 'taggroup:'.$tagGroup->id
    );

    if (craft()->fields->saveField($tagsField))
    {
      Craft::log('InstaBlog Tags field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog Tags field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // InstaBlog category group
    Craft::log('Creating the InstaBlog category group.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $categoryGroup = new CategoryGroupModel();
    $categoryGroup->name   = 'InstaBlog Categories';
    $categoryGroup->handle = 'instaBlogCategories';
    $categoryGroup->template = 'blog/category';
    $categoryGroup->maxLevels = 1;

    // Locale-specific URL formats
    $locales = array();

    foreach (craft()->i18n->getSiteLocaleIds() as $localeId)
    {
      $locales[$localeId] = new CategoryGroupLocaleModel(array(
        'locale'          => $localeId,
        'urlFormat'       => 'blog/category/{slug}',
        'nestedUrlFormat' => null,
      ));
    }

    $categoryGroup->setLocales($locales);

    // Group the field layout
    $categoryFieldLayout = craft()->fields->assembleLayout(
      array(
        'Content' => array($bodyField->id)
      ),
      array()
    );
    $categoryFieldLayout->type = ElementType::Category;
    $categoryGroup->setFieldLayout($categoryFieldLayout);

    // Save it
    if (craft()->categories->saveGroup($categoryGroup))
    {
      Craft::log('InstaBlog category group created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog category group.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Categories field
    Craft::log('Creating the InstaBlog Category field.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $categoriesField = new FieldModel();
    $categoriesField->groupId      = $group->id;
    $categoriesField->name         = 'InstaBlog Categories';
    $categoriesField->handle       = 'instaBlogCategories';
    $categoriesField->type         = 'Categories';
    $categoriesField->settings = array(
      'source' => 'group:'.$categoryGroup->id
    );
    if (craft()->fields->saveField($categoriesField))
    {
      Craft::log('InstaBlog Category field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog Category field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // Asset field
    Craft::log('Creating the InstaBlog Asset field.');

    $assetField = new FieldModel();
    $assetField->groupId      = $group->id;
    $assetField->name         = 'Featured Image';
    $assetField->handle       = 'instaBlogImage';
    $assetField->translatable = false;
    $assetField->type         = 'Assets';
    $assetField->settings = array(
      'sources' => '*'
    );

    if (craft()->fields->saveField($assetField))
    {
      Craft::log('Asset field created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the Asset field.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // InstaBlog section
    Craft::log('Creating the InstaBlog section.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $instaBlogSection = new SectionModel();
    $instaBlogSection->type     = SectionType::Channel;
    $instaBlogSection->name     = 'InstaBlog';
    $instaBlogSection->handle   = 'instaBlog';
    $instaBlogSection->hasUrls  = true;
    $instaBlogSection->template = 'blog/_entry';


    // Locale-specific URL formats
    $locales = array();

    if (craft()->isLocalized())
    {
      $primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
      $localeIds = craft()->i18n->getSiteLocaleIds();
    }
    else
    {
      $primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
      $localeIds = array($primaryLocaleId);
    }

    foreach ($localeIds as $localeId)
    {
      $locales[$localeId] = new SectionLocaleModel(array(
        'locale'           => $localeId,
        'enabledByDefault' => true,
        'urlFormat'        => 'blog/{slug}',
      ));
    }

    $instaBlogSection->setLocales($locales);

    if (craft()->sections->saveSection($instaBlogSection))
    {
      Craft::log('InstaBlog section created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog section.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }

    // InstaBlog section entry type layout
    Craft::log('Saving the InstaBlog entry type.',
     LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');

    $instaBlogLayout = craft()->fields->assembleLayout(
      array(
        'Content' => array($bodyField->id, $assetField->id, $categoriesField->id, $tagsField->id),
      ),
      array($bodyField->id)
    );

    $instaBlogLayout->type = ElementType::Entry;

    $instaBlogEntryTypes = $instaBlogSection->getEntryTypes();
    $instaBlogEntryType = $instaBlogEntryTypes[0];
    $instaBlogEntryType->setFieldLayout($instaBlogLayout);

    if (craft()->sections->saveEntryType($instaBlogEntryType))
    {
      Craft::log('InstaBlog entry type saved successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog entry type.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    // InstaBlog entry
    Craft::log('Creating a InstaBlog entry.');

    $instaBlogEntry = new EntryModel();
    $instaBlogEntry->sectionId  = $instaBlogSection->id;
    $instaBlogEntry->typeId     = $instaBlogEntryType->id;
    $instaBlogEntry->locale     = $primaryLocaleId;
    $instaBlogEntry->authorId   = craft()->userSession->getId();
    $instaBlogEntry->enabled    = true;
    $instaBlogEntry->getContent()->title = 'We just installed InstaBlog!';
    $instaBlogEntry->getContent()->setAttributes(array(
      'instaBlogBody' => '<p>'
          . 'Collaboratively administrate empowered markets via plug-and-play networks. Dynamically procrastinate B2C users after installed base benefits. Dramatically visualize customer directed convergence without revolutionary ROI.'
          . '</p><p>Efficiently unleash cross-media information without cross-media value. Quickly maximize timely deliverables for real-time schemas. Dramatically maintain clicks-and-mortar solutions without functional solutions.'
          . '</p><p>Completely synergize resource taxing relationships via premier niche markets. Professionally cultivate one-to-one customer service with robust ideas. Dynamically innovate resource-leveling customer service for state of the art customer service.'
          . '</p>',
    ));

    if (craft()->entries->saveEntry($instaBlogEntry))
    {
      Craft::log('InstaBlog entry created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not save the InstaBlog entry.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    //Create Route for Tags
    $urlParts = array('blog/tag/',
                  array(
                    '*',
                    '[^\/]+'
                  ));
    $template = 'blog/tag';

    if (craft()->routes->saveRoute($urlParts, $template))
    {
      Craft::log('InstaBlog tag route created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not create the InstaBlog tag route.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }



    //Create Route for Tags
    $urlParts = array('blog/author/',
                  array(
                    '*',
                    '[^\/]+'
                  ));
    $template = 'blog/author';

    if (craft()->routes->saveRoute($urlParts, $template))
    {
      Craft::log('InstaBlog author route created successfully.',
       LogLevel::Info, true, '_createInstaBlogContent', 'InstaBlog');
    }
    else
    {
      Craft::log('Could not create the InstaBlog author route.',
       LogLevel::Error, true, '_createInstaBlogContent', 'InstaBlog');
    }

  }



  /**
   * Copies template files to templates folder
   *
   * @return null
   */
  private function _copyTemplates()
  {
    $instaBlogFolder = trim(realpath(dirname(__FILE__)), 'services')
      .'resources/_templates/';
    $craftTemplateFolder = realpath(CRAFT_TEMPLATES_PATH);
    $instaBlogTargetFolder = $craftTemplateFolder.'/blog/';

    Craft::log('Creating blog folder in templates directory.',
     LogLevel::Info, true, '_copyTemplates', 'InstaBlog');

    IOHelper::createFolder($instaBlogTargetFolder,0755,true);

    Craft::log('Copying InstaBlog templates to templates/blog directory.',
     LogLevel::Info, true, '_copyTemplates', 'InstaBlog');

    if (IOHelper::copyFolder($instaBlogFolder, $instaBlogTargetFolder, true))
    {
      Craft::log($instaBlogFolder.' copied to '.$instaBlogTargetFolder.' successfully.',
       LogLevel::Info, true, '_copyTemplates', 'InstaBlog');
    }
    else
    {
      Craft::log('Failed copying '.$instaBlogFolder.' to '.$instaBlogTargetFolder,
       LogLevel::Error, true, '_copyTemplates', 'InstaBlog');
    }
  }
}


?>
