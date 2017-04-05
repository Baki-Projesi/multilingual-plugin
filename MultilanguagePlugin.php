<?php
class MultilanguagePlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
            'install',
            'uninstall',
            'config',
            'config_form',
            'admin_head',
            'admin_footer',
            'initialize',
            'exhibits_browse_sql',
            'simple_pages_pages_browse_sql',
            'public_head',
            'public_header'
            );
    
    protected $_filters = array(
            'locale',
            'guest_user_links',
            'admin_navigation_main',
            'public_navigation_main'
            );
    
    protected $_translationTable = null;
    
    protected $locale_code;
    
    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'multilanguage_lang' => 'es'
    );

    /* 
     *  Load javascript on public pages
     *  jQuery cookie no longer being developed. Superseded by library below with similair api
     */
    public function hookPublicHead() {
        queue_js_url('https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.0.3/js.cookie.min.js');
        queue_js_file('jq-cookie-locale');
    }
    /* 
     *  Add hidden fields to pass data to js
     */
    public function hookPublicHeader() {
        $altLocale = $this->getAltLocale();
        $defaultLocale = $this->getDefaultLocale();
        echo "<input id='default_lang' type='hidden' value='". $defaultLocale['language'] . "' />";
        echo "<input id='alt_lang' type='hidden' value='" .  $altLocale['language'] . "' />";
        echo "<input id='alt_loc' type='hidden' value='" . $altLocale['locale'] . "' />";
        echo "<input id='default_loc' type='hidden' value='" . $defaultLocale['locale'] . "' />";
        
       //    echo "<h2>current url: " . current_url() . "</h2>";
       // Is this a Simple Page? If so, find its record in SimplePagesPage table by slug
       // $slug = ltrim(current_url(), "/");
       $which_page = current_url();
       $slug = ltrim(strrchr($which_page, '/'), "/");
       //echo "<h4>slug: " . $slug. "</h4>";
       $SimplePage = get_db()->getTable('SimplePagesPage')->findBy(array('slug' => $slug ));

       // If this is a SimplePage, find the page it's mapped to.
       if (!empty($SimplePage)) {
            $page_id = $SimplePage[0]['id'];
            $cl_page = get_db()->getTable('MultilanguageContentLanguage')->findBy(array('record_id' => $page_id, 'record_type' => 'SimplePagesPage'));
            $map_to_id = $cl_page[0]['map_to_id'];
            //echo "<h4>map_to_id is $map_to_id </h4>";
            $map_page = get_db()->getTable('SimplePagesPage')->find($map_to_id);
            // If there is a mapped page, put its slug in a hidden input field for js to grab.
            if (isset($map_page)) {   
                echo "<input id='map_to_slug' type='hidden' value='" . $map_page['slug'] . "' />";
            }
       } else {
           echo "<input id='map_to_slug' type='hidden' value='' />";
       }
    }
    
   /*
    * Add Link to Public Navigation (with Browse Items, Browse Collections, etc.)
    */
    public function filterPublicNavigationMain($nav)
    { 
        // Get default locale from config fle
        $defaultLocale = $this->getDefaultLocale();
        $currentLocale = $this->getCurrentLocale();
        $altLocale = $this->getAltLocale();

        // Set navigation label to either the Alternate or the Default locale (English) if language cookie isn't set
        $cookied = (isset($_COOKIE['multi-loc'])) ? $_COOKIE['multi-loc'] : false;

        if($cookied == 'es') {
            $label = 'English';
        } elseif($cookied == 'en') {
            $label = 'español';
        } else {
            if (strlen($altLocale['language']) > 0) {
                $label = $altLocale['language'];
            } else {
                $label = $altLocale['locale'];
            }

            if ($currentLocale['locale'] == $altLocale['locale']) {
                if (strlen($defaultLocale['language']) > 0) {
                    $label = $defaultLocale['language'];
                } else {
                    $label =  $defaultLocale['locale'];
                }
            }
        }

        $nav[] = array(
            'label' => $label,
            'uri' => "/",
            'id' => 'translate',
            'class' => 'button',
        );
        

        return $nav;
    }
    
    public function filterPublicNavigationItems($navArray){
        $navArray[] = array('label'=> __('My Plugin Items'),
                        'uri' => url('/about')
                        );
    }
    
    public function hookInitialize($args)
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }
    
    public function hookExhibitsBrowseSql($args)
    {
        $this->modelBrowseSql($args, 'Exhibit');
    }

    public function hookSimplePagesPagesBrowseSql($args)
    {
        $this->modelBrowseSql($args, 'SimplePagesPage');
    }
    
    public function modelBrowseSql($args, $model)
    {
        if (! is_admin_theme()) {
            $select = $args['select'];
            $db = get_db();
            $alias = $db->getTable('MultilanguageContentLanguage')->getTableAlias();
            $modelAlias = $db->getTable($model)->getTableAlias();
            $select->joinLeft(array($alias => $db->MultilanguageContentLanguage),
                            "$alias.record_id = $modelAlias.id", array());
            $select->where("$alias.record_type = ?", $model);
            $select->where("$alias.lang = ?", $this->locale_code);
        }
    }
    public function filterGuestUserLinks($links)
    {
        $links['Multilanguage'] = array('label' => __('Preferred Language'), 'uri' => url('multilanguage/user-language/user-language'));
        return $links;
    }
    
    public function filterAdminNavigationMain($nav)
    {
      //  $nav['Multilanguage'] = array('label' => __('Preferred Language'), 'uri' => url('multilanguage/user-language/user-language'));
        $nav['Multilanguage_content'] = array(
                'label' => __('Multilanguage Content'),
                'uri'   => url('multilanguage/translations/content-language')
                );
        return $nav;
    }
   
     public function filterLocale($locale)
    //public function filterLocale()
    {
        $defaultCodes = Zend_Locale::getDefault();
        $defaultCode = current(array_keys($defaultCodes));
//        if (empty($locale)) {
//            $this->locale_code = $defaultCode;
//        } else {
//            $this->locale_code = $locale;
//        }
//        $this->_translationTable = $this->_db->getTable('MultilanguageTranslation');
//        $user = current_user();
//        $userPrefLanguageCode = false;
//        $userPrefLanguage = false;
//        if ($user) {
//            $prefLanguages = $this->_db->getTable('MultilanguageUserLanguage')->findBy(array('user_id' => $user->id));
//            if ( ! empty($prefLanguages)) {
//                $userPrefLanguage = $prefLanguages[0];
//                $userPrefLanguageCode = $userPrefLanguage->lang;
//                $this->locale_code = $userPrefLanguageCode;
//            }
//        }
//        if (! $userPrefLanguageCode) {
//            $codes = unserialize( get_option('multilanguage_language_codes') );
//            //dump the site's default code to the end as a fallback
//            $codes[] = $defaultCode;
//            $browserCodes = array_keys(Zend_Locale::getBrowser());
//            foreach ($browserCodes as $browserCode) {
//                if (in_array($browserCode, $codes)) {
//                    $this->locale_code = $browserCode;
//                    break;
//                }
//            }
//        }
        
        //weird to be adding filters here, but translations weren't happening consistently when it was in setUp
        //@TODO: check if this oddity is due to setting the priority high
//        $translatableElements = unserialize(get_option('multilanguage_elements'));
//        if(is_array($translatableElements)) {
//            foreach($translatableElements as $elementSet=>$elements) {
//                foreach($elements as $element) {
//                    add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'translate'), 1);
//                    add_filter(array('ElementInput', 'Item', $elementSet, $element), array($this, 'translateField'), 1);
//                    add_filter(array('Display', 'Collection', $elementSet, $element), array($this, 'translate'), 1);
//                    add_filter(array('ElementInput', 'Collection', $elementSet, $element), array($this, 'translateField'), 1);
//                    add_filter(array('Display', 'File', $elementSet, $element), array($this, 'translate'), 1);
//                    add_filter(array('ElementInput', 'File', $elementSet, $element), array($this, 'translateField'), 1);
//                }
//            }
//        }
   //     return $this->locale_code;
        
        
        $request = new Zend_Controller_Request_Http();
        $cookieData = $request->getCookie('multi-loc');
        if ($cookieData) {
            $this->locale_code = $cookieData;
        } else {
            $this->locale_code = $defaultCode;
        }
      //  echo "<h2>LOCALE CODE IS $this->locale_code </h2>";

        
// CHANGE THIS TO ADD_FILTER FOR EXHIBITS, EXHIBIT PAGES, SIMPLE PAGES ?
        $translatableElements = unserialize(get_option('multilanguage_elements'));
        if(is_array($translatableElements)) {
            foreach($translatableElements as $elementSet=>$elements) {
                foreach($elements as $element) {
                    add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'Item', $elementSet, $element), array($this, 'translateField'), 1);
                    add_filter(array('Display', 'Collection', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'Collection', $elementSet, $element), array($this, 'translateField'), 1);
                    add_filter(array('Display', 'File', $elementSet, $element), array($this, 'translate'), 1);
                    add_filter(array('ElementInput', 'File', $elementSet, $element), array($this, 'translateField'), 1);
                }
            }
        }
   
        return $this->locale_code;
    }
    
    public function hookAdminFooter()
    {
        echo "<div id='multilanguage-modal'>
        <textarea id='multilanguage-translation'></textarea>
        </div>";
        
        echo "<script type='text/javascript'>
        var baseUrl = '" . WEB_ROOT . "';
        </script>
        ";
    }
    
    public function hookAdminHead()
    {
        queue_css_file('multilanguage');
        queue_js_file('multilanguage');
    }
     
    public function hookInstall()
    {
        set_option('multilanguage_lang', 'es');
        
        $db = $this->_db;
        $sql = "
CREATE TABLE IF NOT EXISTS $db->MultilanguageTranslation (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `element_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `record_type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `locale_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci,
  `translation` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `element_id` (`element_id`,`record_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
                    ";
        
        $db->query($sql);
        
        $sql = "
CREATE TABLE IF NOT EXISTS $db->MultilanguageContentLanguage (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `lang` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `map_to_id` int(10),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
        ";
        
        $db->query($sql);
        
        $sql = "

CREATE TABLE IF NOT EXISTS $db->MultilanguageUserLanguage (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `lang` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
        
        ";
        
        $db->query($sql);
    }
    
    public function hookUninstall()
    {
        $db = $this->_db;
        $sql = "DROP TABLE $db->MultilanguageTranslation ";
        $db->query($sql);
        
        $sql = "DROP TABLE $db->MultilanguageContentLanguage ";
        $db->query($sql);

        
        $sql = "DROP TABLE $db->MultilanguageUserLanguage";
        $db->query($sql);
        
    }

    public function hookConfig($args)
    {
        $post = $args['post'];
        $elements = array();
        $elTable = get_db()->getTable('Element');
        foreach($post['element_sets'] as $elId) {
            $element = $elTable->find($elId);
            $elSet = $element->getElementSet();
            if(!array_key_exists($elSet->name, $elements)) {
                $elements[$elSet->name] = array();
            }
            $elements[$elSet->name][] = $element->name;
        }
        set_option('multilanguage_elements', serialize($elements));
        set_option('multilanguage_language_codes', serialize($post['multilanguage_language_codes']));
    }

    public function hookConfigForm()
    {
        include('config_form.php');
    }
    
    // Add to Edit Item page, so that you can translate field content into one or more alternate languages
    public function translateField($components, $args)
    {
        $record = $args['record'];
        $element = $args['element'];
        $type = get_class($record);
        $languages = unserialize(get_option('multilanguage_language_codes'));
        $html = __('Translate to: ');
        foreach ($languages as $code) {
            $html .= " <li data-element-id='{$element->id}' data-code='$code' data-record-id='{$record->id}' data-record-type='{$type}' class='multilanguage-code'>$code</li>";
        }
        $components['form_controls'] .= "<ul class='multilanguage' >$html</ul>";
        return $components;
    }
    
    // Display on public page
    public function translate($translateText, $args)
    {
        $db = $this->_db;
        $record = $args['record'];
        //since I'm being cheap and not differentiating Items vs Collections
        //or any other ActsAsElementText up above in the filter definitions (themselves weird), 
        //I risk getting null values here
        //after the filter happens for 'element_text'
        if (! empty($args['element_text'])) {
            $elementText = $args['element_text'];
            
          //  error_log("element_text = " . $elementText);
        
            $elementId = $elementText->element_id;
            
            $translation = $db->getTable('MultilanguageTranslation')->getTranslation($record->id, get_class($record), $elementId, $this->locale_code, $translateText);
            if ($translation) {
                $translateText = $translation->translation;
                
            //    error_log("translateText = " . $translation->translation);
            }
        }
        return $translateText;
    }
    
    public function getCurrentLocale() {
       $current_locale = get_html_lang();
       $currentLocale = new Zend_Locale($current_locale);
       $currentLang = $currentLocale->getTranslation($currentLocale, 'language', $currentLocale); 
       return array('locale' => $current_locale, 'language' => $currentLang);
    }
    
    public function getDefaultLocale() {
        // Get default locale from config fle
        $configFile = CONFIG_DIR . '/config.ini';
        $options = array('allowModifications' => FALSE);
        $zendConfig = new Zend_Config_Ini($configFile, 'site', $options);
        if (strlen($zendConfig->locale->name) > 0 ) {
            $defaultLocale = new Zend_Locale($zendConfig->locale->name);
        } else {
            $defaultLocale = new Zend_Locale("en");
        }
        $defaultLang = $defaultLocale->getTranslation($defaultLocale, 'language', $defaultLocale); 
        return array('locale' => $defaultLocale, 'language' => $defaultLang);
    }
    
    public function getAltLocale() {
       // Get alternate locale from db options table
       $alt_locale = get_option('multilanguage_lang');
       $altLocale = new Zend_Locale();
       $altLocale->setLocale($alt_locale);
       $alt_lang = $altLocale->getTranslation($alt_locale, 'language', $alt_locale); 
       return array('locale' => $alt_locale, 'language' => $alt_lang);
    }
    
    public function getCookieLocale() {
        $request = new Zend_Controller_Request_Http();
        $multiLocCookie = $request->getCookie('multi-loc');
        return $multiLocCookie;
    }

}