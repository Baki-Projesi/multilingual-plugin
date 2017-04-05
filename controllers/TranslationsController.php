<?php
class Multilanguage_TranslationsController extends Omeka_Controller_AbstractActionController
{
    public function translateAction()
    {
        $db = get_db();
        if ($this->getRequest()->isPost()) {
            if (isset($_POST['translation_id'])) {
                $translation = $db->
                    getTable('MultilanguageTranslation')->find($_POST['translation_id']);
            } else {
                $translation = new MultilanguageTranslation;
            }
            
            $translation->element_id = $_POST['element_id'];
            $translation->record_id = $_POST['record_id'];
            $translation->record_type = $_POST['record_type'];
            $translation->text = $_POST['text'];
            $translation->translation = $_POST['translation'];
            $translation->locale_code = $_POST['locale_code'];
            $translation->save();
            $this->_helper->json('');
        }
    }
    
    public function translationAction()
    {
        $db = get_db();
        
        if (isset($_GET['text'])) {
            $translation = $db->
                getTable('MultilanguageTranslation')
                ->getTranslation(
                        $_GET['record_id'],
                        $_GET['record_type'],
                        $_GET['element_id'],
                        $_GET['locale_code'],
                        $_GET['text']
            );
            if ($translation) {
                $translation = $translation->toArray();
            } else {
                $translation = array('translation' => '');
            }
        } else {
            $translation = array('translation' => '');
        }

        $this->_helper->json($translation);
    }
    
    
    // TO DO: Generalize for Exhibit or SimplePage and fix Exhibit stuff
    // Right now only SimplePage is fleshed out
    
    public function contentLanguageAction()
    {
        $db = get_db();
        if (plugin_is_active('ExhibitBuilder')) {
            $exhibits = $db->getTable('Exhibit')->findAll();
            $this->view->exhibits = $exhibits;
        }
        
        if (plugin_is_active('SimplePages')) {
            $simplePages = $db->getTable('SimplePagesPage')->findAll();
            $this->view->simple_pages = $simplePages;
        }
        
        if ($this->getRequest()->isPost()) {
                         
            if (isset($_POST['exhibits'])) {
                $exhibitLangs = $this->getParam('exhibits');
                foreach ($exhibitLangs as $recordId=>$lang) {
                    $this->updateContentLang('Exhibit', $recordId, $lang, 0);
                }
            }
            if (isset($_POST['simple_pages_page'])) {
                $simplePages = $this->getParam('simple_pages_page');
                foreach ($simplePages as $recordId=>$lang) {
                    $contentLangRecord = $this->fetchContentLanguageRecord('SimplePagesPage', $recordId);
                    if ($contentLangRecord->lang !== $lang) {
                        // If this page already has a mapping, delete it.
                        if ($contentLangRecord->map_to_id > 0) {
                            $this->deleteMapIdFromMappedPage($contentLangRecord->map_to_id);
                            $contentLangRecord->map_to_id = 0;
                        }
                        $this->updateContentLang('SimplePagesPage', $recordId, $lang, $map_to_id);
                    }
                }
            }
            if (isset($_POST['exhibit_map'])) {
                $map_to_id = $this->getParam('exhibit_map');
                $exhibit_id = $this->getParam('exhibit_id');
                $contentLanguage = $this->fetchContentLanguageRecord('Exhibit', $exhibit_id);

                $contentLanguage->map_to_id = $map_to_id;
                $contentLanguage->save();
                $otherContentLanguage = $this->fetchContentLanguageRecord('Exhibit', $map_to_id);
                $otherContentLanguage->map_to_id = $exhibit_id;
                $otherContentLanguage->save();
                $this->_helper->redirector->gotoUrl('/multilanguage/translations/content-language');
            }
            if (isset($_POST['simple_page_map'])) {
                $map_to_id = $this->getParam('simple_page_map');
                $simple_page_id = $this->getParam('page_id');
                $contentLanguage = $this->fetchContentLanguageRecord('SimplePagesPage', $simple_page_id);
                // Is this page already mapped? If so, get the old mapped page and delete its reference to this page.
                if ($contentLanguage->map_to_id > 0 ) {
                    $this->deleteMapIdFromMappedPage($contentLanguage->map_to_id);
                }
                // Save new mapping for this page
                $contentLanguage->map_to_id = $map_to_id;
                $contentLanguage->save();
                // Is the new mapped page already mapped to another page? If so delete the old references.
                $otherContentLanguage = $this->fetchContentLanguageRecord('SimplePagesPage', $map_to_id);
                if ($otherContentLanguage->map_to_id > 0 ) {
                    $this->deleteMapIdFromMappedPage($otherContentLanguage->map_to_id);
                }
                // Save new mapping for the mapped page
                $otherContentLanguage->map_to_id = $simple_page_id;
                $otherContentLanguage->save();
                // Return to list of pages and mappings
                $this->_helper->redirector->gotoUrl('/multilanguage/translations/content-language');
            }
        }      
    }
    
    protected function updateContentLang($recordType, $recordId, $lang, $map_to_id)
    {
        $contentLanguage = $this->fetchContentLanguageRecord($recordType, $recordId);
        $contentLanguage->record_type = $recordType;
        $contentLanguage->record_id = $recordId;
        $contentLanguage->lang = $lang;
        $contentLanguage->map_to_id = $map_to_id;
        $contentLanguage->save();
    }
    
    protected function fetchContentLanguageRecord($recordType, $recordId)
    {
        $table = $this->_helper->db->getTable('MultilanguageContentLanguage');
        $select = $table->getSelectForFindBy(
                array('record_type' => $recordType,
                      'record_id'   => $recordId,
                ));
        $contentLanguage = $table->fetchObject($select);
        if ($contentLanguage) {
            return $contentLanguage;
        } else {
            return new MultilanguageContentLanguage;
        }
    }
    
    // Find the record for the page this page is mapped to. Then delete the 
    // mapping from the mapped page's record.
    protected function deleteMapIdFromMappedPage($map_to_id) {
        $oldMap = $this->fetchContentLanguageRecord('SimplePagesPage', $map_to_id );
        $oldMap->map_to_id = 0;               
        $oldMap->save();
    }
}