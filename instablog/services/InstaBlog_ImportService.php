<?php 
namespace Craft;

class InstaBlog_ImportService extends BaseApplicationComponent
{

  function __construct()
  {
    craft()->config->set('devMode', false);
    craft()->config->maxPowerCaptain();
  }

  /**
   * Parses XML File from Wordpress export. Handles name spacing and returns array
   * containing accessible post ID's.
   *
   * @param string $path // path to XML file
   *
   * @return Array
   */
  public function prepData($path)
  {
    $parser = new \WXR_Parser();
    return @$parser->parse($path);
  }



  /**
  * Process Import Data
  *
  * @param number $step Task number
  * @param mixed $settings Array of settings
  * 
  * @return Boolean
  */
  public function importData($step, $settings)
  {
    $importItem     = explode('_', $settings->import[$step]);
    $itemType       = $importItem[0];
    $itemId         = $importItem[1];
    $data           = $this->prepData($settings->file);
    $categoryGroup  = craft()->categories->getGroupByHandle('instaBlogCategories');
    $tagGroup       = craft()->tags->getTagGroupByHandle('instaBlogTags');

    switch ($itemType)
    {
      case 'authors':
        // Get Author data from WP Import XML
        $this->_importAuthor($data['authors'][$itemId]);
        break;
      
      case 'posts':
        //Get Post data from WP Import XML
        $this->_importPost($settings, $data['posts'][$itemId], $categoryGroup->id, $tagGroup->id, $data['base_url']);
        break;
      
      default:
        return false;
        break;
    }

    return true;
  }



  /**
  * Process Update Links
  *
  * @param number $step Task number
  * @param mixed $settings Array of settings
  * 
  * @return Boolean
  */
  public function updateLinks($step, $settings)
  {

    $importItem     = explode('_', $settings->import[$step]);
    $itemType       = $importItem[0];
    $itemId         = $importItem[1];
    $data           = $this->prepData($settings->file);
    
    if (($itemType === 'posts') && 
      ($entry  = $this->_getEntry($data['posts'][$itemId]['post_name'])) &&
      (!empty($entry->instaBlogBody)))
    {
      $body   = $entry->instaBlogBody;
      $dom    = new \domDocument;

      // load the html into the object
      $dom->loadHTML($body);

      // discard white space
      $dom->preserveWhiteSpace = false;

      $linkEls = $dom->getElementsByTagName('a');

      foreach ($linkEls as $link)
      {
        if (strpos($link->getAttribute('href'), $data['base_url']) !== false)
        {
          // Get slug from href
          $linkParsed   = parse_url($link->getAttribute('href'));
          $slug         = end((explode('/', rtrim($linkParsed['path'], '/'))));
          
          if ($targetEntry  = $this->_getEntry($slug))
          {
            $body = str_replace(
              $link->getAttribute('href'),
              $targetEntry->url . '#entry:' . $targetEntry->id,
              $body);
          }
        }
      }

      $entry->setContentFromPost(array(
        'instaBlogBody'       => $body,
      ));

      if (craft()->entries->saveEntry($entry))
      {
        Craft::log('InstaBlog entry links updated successfully.',
         LogLevel::Info, false, 'updateLinks', 'InstaBlog');
        
        return true;
      }
      else
      {
        Craft::log('Could not update the InstaBlog entry.',
         LogLevel::Error, true, 'updateLinks', 'InstaBlog');

        return false;
      }

    }
    else
    {
      return true;
    }
  }


  /**
  * Import WP Author into users
  *
  * @param mixed $author Author data from WP XML
  * 
  * @return Null
  */
  protected function _importAuthor($author)
  {

    if (!craft()->users->getUserByEmail($author['author_email']))
    {
      if (empty(trim($author['author_first_name']).trim($author['author_last_name'])))
      {
        $author['author_first_name'] = $author['author_login'];
      }

      $user = new UserModel();
      $user->username  = $author['author_login'];
      $user->firstName = $author['author_first_name'];
      $user->lastName  = $author['author_last_name'];
      $user->email     = $author['author_email'];

      if (!$user->validate())
      { 
        $emailParts = explode('@', $author['author_email']);
        $user->username = $emailParts[0];
      }

      if (!craft()->users->saveUser($user))
      {
         Craft::log('Couldn’t save the user "'.$author['author_login'].'"',
          LogLevel::Warning, true, 'application', 'InstaBlog');
      }
    }
    else
    {
      Craft::log('A User already exists with the email: "'.$author['author_email'].'"',
        LogLevel::Info, false, 'application', 'InstaBlog');
    }
  }



  /**
  * Import WP Post into InstaBlog Section
  *
  * @param mixed $settings Array of settings
  * @param mixed $post Entry Posts array from WP XML
  * @param number $categoryGroupId Category Group ID for InstaBlog Categories
  * @param number $tagGroupId Tag Group ID for InstaBlog Tags
  * @param string $baseUrl Base url of WP blog
  * 
  * @return Null
  */
  protected function _importPost($settings, $post, $categoryGroupId, $tagGroupId, $baseUrl)
  {

    $postContent    = $post['post_content'];
    $featuredImage  = array();
    $categoryIds    = array();
    $tagIds         = array();

    // If entry slug exists abort
    if ($this->_getEntry($post['post_name']))
    {
      Craft::log('Could not save the InstaBlog entry:'.$post['post_name'].' It already exists.',
       LogLevel::Info, false, 'application', 'InstaBlog');

      return;
    }

    // Import Tags / Categories associated with post
    if (array_key_exists('terms', $post))
    {
      foreach ($post['terms'] as $term)
      {
        switch($term['domain'])
        {
          case 'category':
            $categoryIds[] = $this->_importCategory($term['name'], $categoryGroupId);
            break;

          case 'post_tag':
            $tagIds[] = $this->_importTag($term['name'], $tagGroupId);
            break;
        }
      }
    }

    // Import Featured Images
    foreach ($post['postmeta'] as $postmetaArray)
    {
      if ($postmetaArray['key'] === '_thumbnail_id')
      {
        $featuredImage[] = $this->_importFeaturedImage($settings, $post['post_link'], $baseUrl);
      }
    }

    // Import Images referenced in post_content
    $postContent = $this->_importImages($settings, $postContent, $baseUrl);

    // Replace missing paragraph elements. Nice goin' WordPress.
    $postContent = $this->_wpautop($postContent);

    // Get values for entry
    $section          = craft()->sections->getSectionByHandle('instaBlog');
    $entryTypes       = $section->getEntryTypes();
    $entryType        = $entryTypes[0];

    // Save Entry
    $entry = new EntryModel();
    $entry->sectionId            = $section->id;
    $entry->typeId               = $entryType->id;
    $entry->locale               = craft()->i18n->getPrimarySiteLocaleId();
    $entry->authorId             = $this->_getUserId($post['post_author']);
    $entry->enabled              = ($post['status'] === 'publish') ? true : false;
    $entry->postDate             = $post['post_date'];
    $entry->slug                 = $post['post_name'];
    $entry->getContent()->title  = $post['post_title'];
    $entry->setContentFromPost(array(
      'instaBlogBody'       => $postContent,
      'instaBlogImage'      => $featuredImage,
      'instaBlogCategories' => $categoryIds,
      'instaBlogTags'       => $tagIds,
    ));

    if (craft()->entries->saveEntry($entry))
    {
      Craft::log('InstaBlog entry created successfully.',
       LogLevel::Info, false, '_importPost', 'InstaBlog');

    }
    else
    {
      Craft::log('Could not save the InstaBlog entry.',
       LogLevel::Error, true, '_importPost', 'InstaBlog');
    }
  }



  /**
  * Get entry if entry exists
  *
  * @param string $slug Entry slug
  * 
  * @return Mixed Entry if exists otherwise false
  */
  protected function _getEntry($slug)
  {
    // Check to see if category exists
    $criteria                 = craft()->elements->getCriteria(ElementType::Entry);
    $criteria->slug           = $slug;
    $criteria->limit          = null;
    $criteria->status         = null;
    $criteria->localeEnabled  = null;
    $findEntry                = $criteria->first();

    return (empty($findEntry)) ? false : $findEntry;
  }



  /**
   * Gets user Id. If user doesn't exist, return current session user.
   *
   * @param string $author Username for author user
   *
   * @return integer Id for user.
   */
  protected function _getUserId($username)
  {
    $author   = craft()->users->getUserByUsernameOrEmail($username);
    $authorId = ($author) ? $author->id : craft()->userSession->getId();

    return $authorId;
  }



  /**
   * Saves category from wordpress
   *
   * @param string $title Title for new/existing category
   * @param number $categoryGroupId Category group id for saving category
   *
   * @return integer Id for category.
   */
  protected function _importCategory($title, $categoryGroupId)
  {
    // Check to see if category exists
    $criteria           = craft()->elements->getCriteria(ElementType::Category);
    $criteria->groupId  = $categoryGroupId;
    $criteria->status   = null;
    $criteria->limit    = null;
    $findCategories     = $criteria->find();
    $match              = false;

    foreach ($findCategories as $findCategory)
    {
      if ($findCategory->title == $title)
      {
        return $findCategory->id;
      }
    }
    
    // Save the category
    $category                       = new CategoryModel();
    $category->groupId              = $categoryGroupId;
    $category->getContent()->title  = $title; 

    if(!craft()->categories->saveCategory($category))
    {
      Craft::log('Couldn’t save the category "'.$title.'"',
        LogLevel::Warning, true, '_importCategory', 'InstaBlog');

      return false;
    }
    else
    {
      return $category->id;
    }
  }



  /**
   * Saves tag from wordpress
   *
   * @param string $name Title for new/existing category
   * @param string $tagGroupId Tag group id for saving tags
   *
   * @return integer Id for tag.
   */
  protected function _importTag($name, $tagGroupId)
  {

    // Check to see if category exists
    $criteria           = craft()->elements->getCriteria(ElementType::Tag);
    $criteria->groupId  = $tagGroupId;
    $criteria->limit    = null;
    $findTags           = $criteria->find();
    $match              = false;

    foreach ($findTags as $findTag)
    {
      if ($findTag->name == $name)
      {
        return $findTag->id;
      }
    }

    // Save the tag
    $tag                      = new TagModel();
    $tag->groupId             = $tagGroupId;
    $tag->getContent()->title = $name;

    if(!craft()->tags->saveTag($tag))
    {
      Craft::log('Couldn’t save the tag "'.$name.'"',
        LogLevel::Warning, true, '_importTag', 'InstaBlog');

      return false;
    }
    else
    {
      return $tag->id;
    }
  }



  /**
   * Check upload permissions.
   *
   * @param int $folderId
   *
   * @return null
   */
  private function _checkUploadPermissions($folderId)
  {
    $folder = craft()->assets->getFolderById($folderId);

    // if folder exists and the source ID is null, it's a temp source and we always allow uploads there.
    if (!(is_object($folder) && is_null($folder->sourceId)))
    {
      craft()->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
    }
  }



  /**
   * Import Featured Image from Posts
   *
   * @param mixed $settings Array of settings
   * @param string $postUrl Url of WordPress post
   * @param string $baseUrl domain and uri path to Wordpress site
   *
   * @return string $postContent Post content with image url attributes updated.
   */
  private function _importFeaturedImage($settings, $postUrl, $baseUrl)
  {

    // Scrape post for featured image
    $tempFileName     = md5($postUrl).'.tmp';
    $tempFolder       = craft()->path->getStoragePath().'instablog/';
    $tempFile         = $tempFolder.$tempFileName;
    $postUrl          = $this->_getAbsoluteUrl($postUrl, $baseUrl);
    $curlResponse     = $this->_getRemoteFile($postUrl, $tempFile);
    $remoteImagePath  = false;

    if ($curlResponse && (false === IOHelper::isFileEmpty($tempFile, true)))
    {
      $dom = new \domDocument;

      // load the html into the object
      $dom->loadHTMLFile($tempFile);

      $dom->preserveWhiteSpace = false;

      $imgEls = $dom->getElementsByTagName('img');

      foreach ($imgEls as $img)
      {
        if (strpos($img->getAttribute('class'), 'wp-post-image'))
        {
          $remoteImagePath = $img->getAttribute('src');
        }
      }

      IOHelper::deleteFile($tempFile, true);
    }

    // Add asset
    if ($remoteImagePath)
    {
      if ($assetId = $this->_addAsset($settings, $remoteImagePath, $baseUrl, false))
      {
        return $assetId;
      }
    }

    return false;  
  }



  /**
   * Import Images from Posts
   *
   * @param mixed $settings Array of settings
   * @param string $postContent body of WordPress post
   * @param string $baseUrl domain and uri path to Wordpress site
   *
   * @return string $postContent Post content with image url attributes updated.
   */
  private function _importImages($settings, $postContent, $baseUrl)
  {
    // Stop if getImagesFromPost returns false
    if(!$images = $this->_getImageUrlsFromPost($postContent))
    {
      return $postContent;
    }

    foreach ($images as $src => $attributes)
    {
      if ($assetId = $this->_addAsset($settings, $src, $baseUrl))
      {
        // If successful replace urls in postContent.
        $asset        = craft()->assets->getFileById($assetId['asset']);
        $assetSrc     = $asset->getUrl();
        $postContent  = str_replace(
          $src,
          $assetSrc.'#asset:'.$assetId['asset'].':url',
          $postContent);

        // If original full sized image was imported replace urls in postContent.
        if ((array_key_exists('original', $assetId)) && ($assetId['original']))
        {
          $assetOriginal    = craft()->assets->getFileById($assetId['original']);
          $assetOriginalSrc = $assetOriginal->getUrl();
          $postContent      = str_replace(
            $assetId['originalSrc'],
            $assetOriginalSrc .'#asset:'.$assetId['original'].':url', 
            $postContent);
        }
      }
    }

    return $postContent;
  }


  
  /**
   * Gets images from post body
   *
   * @param string $postContent body of WordPress post
   *
   * @return array/bool Array of urls to remote assets
   */
  private function _getImageUrlsFromPost($postContent)
  {
    if (empty($postContent))
    {
      return false;
    }

    $images = array();

    $dom = new \domDocument;

    // load the html into the object
    $dom->loadHTML($postContent);

    // discard white space
    $dom->preserveWhiteSpace = false;

    $imgEls = $dom->getElementsByTagName('img');

    foreach ($imgEls as $img)
    {
      $images[$img->getAttribute('src')] = array(
        'alt'     => $img->getAttribute('alt'),
        'src'     => $img->getAttribute('src'),
        'height'  => $img->getAttribute('height'),
        'width'   => $img->getAttribute('width')
      );
    }

    return $images;
  }



  /**
   * Adds remote image file as asset
   *
   * @param mixed $settings Array of settings
   * @param string $remoteImagePath url of remote image
   * @param string $baseUrl domain and uri path to Wordpress site
   * @param bool $returnArray Return array or int of Asset Id's
   *
   * @return bool / array File Ids
   */
  private function _addAsset($settings, $remoteImagePath, $baseUrl, $returnArray = true)
  {
    $assetIds           = array();
    $tempFolder         = craft()->path->getStoragePath().'instablog/';
    $remoteImagePath    = $this->_getAbsoluteUrl($remoteImagePath, $baseUrl);
    $remoteImageParsed  = parse_url($remoteImagePath);
    $imageFileName      = IOHelper::getFileName($remoteImageParsed['path']);

    // Ensure folder exists
    IOHelper::ensureFolderExists($tempFolder);

    // Ensure target folder is writable
    try
    {
      $this->_checkUploadPermissions($settings->assetDestination);
    }
    catch (Exception $e)
    {
      Craft::log(var_export($e->getMessage(),true),
        LogLevel::Error, true, '_addAsset', 'InstaBlog');

      return false;
    }    

    // Check to see if this is a WP resized image
    if (preg_match( '|-([\d]+)x([\d]+)|i', $imageFileName, $resizeDimentions))
    {
      // WP dimentions detected in filename. Attempt to get original size image.
      $assetIds['original'] = $this->_addAsset(
        $settings,
        str_replace($resizeDimentions[0], '', $remoteImagePath),
        $baseUrl,
        false);

      $assetIds['originalSrc'] = str_replace($resizeDimentions[0], '', $remoteImagePath);
    
    // Check to see if this is a Wordpress.com resized image (example: filename.ext?w=XXX)
    }
    else if (array_key_exists('query', $remoteImageParsed))
    {
      parse_str($remoteImageParsed['query'], $params);
      if (array_key_exists('w', $params)) 
      {
        // WP dimentions detected in parameters. Attempt to import original size image.
        $assetIds['original'] = $this->_addAsset(
          $settings,
          UrlHelper::stripQueryString($remoteImagePath),
          $baseUrl,
          false);
        $assetIds['originalSrc'] = UrlHelper::stripQueryString($remoteImagePath);

        // Add width dimension to asset filename to differentiate from original size image.
        $imageFileNameParts     = explode('.', $imageFileName);
        $imageFileNameParts[0] .= '-'.$params['w'];
        $imageFileName          = implode('.', $imageFileNameParts);
      }
    }

    // Temp Local Image
    $tempLocalImage = $tempFolder.$imageFileName;
    $curlResponse   = $this->_getRemoteFile($remoteImagePath, $tempLocalImage);

    if ($curlResponse && $this->_validateImage($remoteImagePath, $tempLocalImage))
    {
      $response = craft()->assets->insertFileByLocalPath(
        $tempLocalImage,
        $imageFileName,
        $settings->assetDestination,
        AssetConflictResolution::KeepBoth
      );

      $fileId             = $response->getDataItem('fileId');
      $assetIds['asset']  = $fileId;
    }
    else
    {
      Craft::log('Unable to import '.$remoteImagePath,
        LogLevel::Error, true, '_addAsset', 'InstaBlog');

      return false;
    }

    IOHelper::deleteFile($tempLocalImage, true);

    return ($returnArray) ? $assetIds : $assetIds['asset'];
  }



  /**
   *  Convert relative urls to absolute
   *
   * @param string $url Url to make absolute
   * @param string $baseUrl Base url to WordPress blog
   *
   * @return string Absolute Url
   */
  private function _getAbsoluteUrl($url, $baseUrl)
  {
    $baseUrlParsed      = parse_url($baseUrl);
    $urlParsed          = parse_url($url);

    // Check for root relative image paths and prepend domain if nessessary
    if (UrlHelper::isRootRelativeUrl($url))
    {
      $url = $baseUrlParsed['scheme']
      . '://'
      . $baseUrlParsed['host']
      . $url;
    }
    else if (UrlHelper::isProtocolRelativeUrl($url))
    {
      $url = $baseUrlParsed['scheme'] . '://' . $url;
    }

    return $url;
  }



  /**
   * Validates that temp file is actually an image file
   *
   * @param string $remoteImagePath url of remote image
   * @param string $tempLocalImage file pointer to temp image
   *
   * @return boolean
   */
  private function _validateImage($remoteImagePath, $tempLocalImage)
  {
    // Check to make sure the asset is an image
    if ((IOHelper::getFileKind(IOHelper::getExtension($tempLocalImage)) === 'image') &&
      (substr(IOHelper::getMimeType($tempLocalImage), 0, 5) === 'image'))
    {
      return true;
    }

    return false;
  }




  /**
   * Get a remote file via curl
   *
   * @param string $remoteFileUrl url of remote file
   * @param string $tempLocalFile file pointer to temp file
   *
   * @return Mixed curl response or false
   */
  private function _getRemoteFile($remoteFileUrl, $tempLocalFile)
  {
    $remoteFile = curl_init($remoteFileUrl);
    $tempFile   = @fopen($tempLocalFile, 'wb');

    if (is_file($tempLocalFile))
    {
      curl_setopt($remoteFile, CURLOPT_FILE, $tempFile);
      curl_setopt($remoteFile, CURLOPT_HEADER, 0);
      curl_setopt($remoteFile, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($remoteFile, CURLOPT_MAXREDIRS, 2);
      $curlResponse = curl_exec($remoteFile);
      curl_close($remoteFile);
      fclose($tempFile);
      return $curlResponse;
    }

    return false;
  }



  /**
   * Function taken from Wordpress to handle WP's convoluted formatting.
   *
   * Replaces double line-breaks with paragraph elements.
   *
   * A group of regex replaces used to identify text formatted with newlines and
   * replace double line-breaks with HTML paragraph tags. The remaining line-breaks
   * after conversion become <<br />> tags, unless $br is set to '0' or 'false'.
   *
   *
   * @param string $pee The text which has to be formatted.
   * @param bool   $br  Optional. If set, this will convert all remaining line-breaks
   *                    after paragraphing. Default true.
   * @return string Text which has been converted into correct paragraph tags.
   */
  private function _wpautop( $pee, $br = true )
  {
    $pre_tags = array();

    if ( trim($pee) === '' )
      return '';

    // Just to make things a little easier, pad the end.
    $pee = $pee . "\n";

    /*
     * Pre tags shouldn't be touched by autop.
     * Replace pre tags with placeholders and bring them back after autop.
     */
    if ( strpos($pee, '<pre') !== false ) {
      $pee_parts = explode( '</pre>', $pee );
      $last_pee = array_pop($pee_parts);
      $pee = '';
      $i = 0;

      foreach ( $pee_parts as $pee_part ) {
        $start = strpos($pee_part, '<pre');

        // Malformed html?
        if ( $start === false ) {
          $pee .= $pee_part;
          continue;
        }

        $name = "<pre wp-pre-tag-$i></pre>";
        $pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';

        $pee .= substr( $pee_part, 0, $start ) . $name;
        $i++;
      }

      $pee .= $last_pee;
    }
    // Change multiple <br>s into two line breaks, which will turn into paragraphs.
    $pee = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee);

    $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

    // Add a single line break above block-level opening tags.
    $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);

    // Add a double line break below block-level closing tags.
    $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);

    // Standardize newline characters to "\n".
    $pee = str_replace(array("\r\n", "\r"), "\n", $pee);

    // Collapse line breaks before and after <option> elements so they don't get autop'd.
    if ( strpos( $pee, '<option' ) !== false ) {
      $pee = preg_replace( '|\s*<option|', '<option', $pee );
      $pee = preg_replace( '|</option>\s*|', '</option>', $pee );
    }

    /*
     * Collapse line breaks inside <object> elements, before <param> and <embed> elements
     * so they don't get autop'd.
     */
    if ( strpos( $pee, '</object>' ) !== false ) {
      $pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
      $pee = preg_replace( '|\s*</object>|', '</object>', $pee );
      $pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
    }

    /*
     * Collapse line breaks inside <audio> and <video> elements,
     * before and after <source> and <track> elements.
     */
    if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
      $pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
      $pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
      $pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
    }

    // Remove more than two contiguous line breaks.
    $pee = preg_replace("/\n\n+/", "\n\n", $pee);

    // Split up the contents into an array of strings, separated by double line breaks.
    $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);

    // Reset $pee prior to rebuilding.
    $pee = '';

    // Rebuild the content as a string, wrapping every bit with a <p>.
    foreach ( $pees as $tinkle ) {
      $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
    }

    // Under certain strange conditions it could create a P of entirely whitespace.
    $pee = preg_replace('|<p>\s*</p>|', '', $pee);

    // Add a closing <p> inside <div>, <address>, or <form> tag if missing.
    $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);

    // If an opening or closing block element tag is wrapped in a <p>, unwrap it.
    $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

    // In some cases <li> may get wrapped in <p>, fix them.
    $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee);

    // If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
    $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
    $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);

    // If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
    $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);

    // If an opening or closing block element tag is followed by a closing <p> tag, remove it.
    $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

    // Optionally insert line breaks.
    if ( $br ) {
      // Normalize <br>
      $pee = str_replace( array( '<br>', '<br/>' ), '<br />', $pee );

      // Replace any new line characters that aren't preceded by a <br /> with a <br />.
      $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);

      // Replace newline placeholders with newlines.
      $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
    }

    // If a <br /> tag is after an opening or closing block tag, remove it.
    $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);

    // If a <br /> tag is before a subset of opening or closing block tags, remove it.
    $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
    $pee = preg_replace( "|\n</p>$|", '</p>', $pee );

    // Replace placeholder <pre> tags with their original content.
    if ( !empty($pre_tags) )
      $pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);

    return $pee;
  }
}