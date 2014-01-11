<?php if(!defined('IS_CMS')) die();
/***************************************************************
 *
* Plugin fuer moziloCMS, welches Hinweisetexte für Externe Links anzeigt
* by black-night - Daniel Neef
*
***************************************************************/
class ExternalLink extends Plugin {
    
    private $lang_admin;
//     private $lang_cms;
    const DESTINATION_URL = 'du';
    const HINTSITE = 'hs';
    const FORWARDING_TIME = 'ft';
        
    function getContent($value) {
      global $CMS_CONF;
      global $specialchars;
//       $this->lang_cms = new Language($this->PLUGIN_SELF_DIR."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".txt");        
      $default = array('','name' => 'Link','site'=>$this->settings->get("hint_page_select"),'time'=>$this->settings->get("forwarding_time"));
      $values = $this->makeUserParaArray($value,$default);
      $destinationUrl = getRequestValue(self::DESTINATION_URL,'get');
      if (empty($destinationUrl)) {
          return $this->getLink($values[0],$values['name'],$values['site'],$values['time']);
      } else {          
          $this->getSite($specialchars->rebuildSpecialChars($destinationUrl,false,false),$specialchars->rebuildSpecialChars($destinationUrl,false,false));
          return null;
      }
    }
    function getConfig() {
        global $CatPage;
        global $specialchars;
        $hint_pages = array();
        $cats = $CatPage->get_CatArray(true,false);        
        foreach ($cats as $cat) {
            $cat = $specialchars->rebuildSpecialChars($cat,false,false);
            $pages = $CatPage->get_PageArray($cat,array(EXT_HIDDEN,EXT_PAGE));
            foreach ($pages as $page) {
                $page = $specialchars->rebuildSpecialChars($page,false,false);
                $hint_pages[$cat.':'.$page] = $cat.'/'.$page;
            }
        }
        $config = array();
        $config['hint_page_select'] = array(
                "type" => "select",
                "description" => $this->lang_admin->getLanguageValue("config_hint_page_select"),
                "descriptions" => $hint_pages,
                "multiple" => "false"
        );    
        $config['forwarding_time'] = array(
                "type" => "text",
                "description" => $this->lang_admin->getLanguageValue("config_forwarding_time"),
                "regex" => "/^[1-9][0-9]?/",
                "regex_error" => $this->lang_admin->getLanguageValue("config_number_regex_error")
        );
        return $config;
    }
    function getInfo() {
        global $ADMIN_CONF;
        $this->lang_admin = new Language($this->PLUGIN_SELF_DIR."sprachen/admin_language_".$ADMIN_CONF->get("language").".txt");
        $info = array(
            // Plugin-Name (wird in der Pluginübersicht im Adminbereich angezeigt)
            $this->lang_admin->getLanguageValue("plugin_name").' Revision: 1',
            // CMS-Version
            "2.0",
            // Kurzbeschreibung
            $this->lang_admin->getLanguageValue("plugin_desc"),
            // Name des Autors
            "black-night",
            // Download-URL
            array("http://www.black-night.org","Software by black-night"),
            // Platzhalter => Kurzbeschreibung, für Inhaltseditor
            array('{ExternalLink|URL}' => $this->lang_admin->getLanguageValue("plugin_ohne_param"),
                  '{ExternalLink|URL,...}' => $this->lang_admin->getLanguageValue("plugin_mit_param"))
            );
        return $info;        
    }
  
    private function getLink($url,$urlName,$site,$time) {
        global $CatPage;
        global $CMS_CONF;      
        if ($CMS_CONF->get('targetblank_link')) 
            $target = ' target="_blank"';
        else 
            $target = '';
        return '<a href="'.$CatPage->get_Href(CAT_REQUEST,PAGE_REQUEST,self::DESTINATION_URL.'='.$url.
                                                                   '&'.self::HINTSITE.'='.$site.
                                                                   '&'.self::FORWARDING_TIME.'='.$time).
                                                                   '"'.$target.'>'.$urlName.'</a>';          
    }
    
    private function getSite($url,$urlName) {
        global $CatPage;
    	global $syntax;
    	$timer = getRequestValue(self::FORWARDING_TIME,'get');
    	if (empty($timer)) 
    	    $timer = $this->settings->get("forwarding_time");    
    	if ($timer > 0) {
    	    $syntax->insert_jquery_in_head('jquery');
    	    $syntax->insert_in_head($this->getHead($url,$timer));
    	}
        $HintCatPage = $CatPage->split_CatPage_fromSyntax(getRequestValue(self::HINTSITE,'get'));
        if (empty($HintCatPage[0]) or empty($HintCatPage[1]))
            $HintCatPage = $CatPage->split_CatPage_fromSyntax($this->settings->get("hint_page_select"));
        $value  = $CatPage->get_PageContent($HintCatPage[0],$HintCatPage[1]);
        $value .= '<br /><br /><a href="'.$url.'">'.$urlName.'</a>'; 
        list($content_first,$content,$content_last) = $syntax->splitContent();
        $syntax->content = $content_first.$value.$content_last;        
        ;
    }
    
    private function getHead($url,$time) {
        return '<script type="text/javascript">$(document).ready(function(){
                                                  window.setTimeout(function(){
                                                    window.location.assign("'.$url.'");
                                                  },'.$time.');
                                                }); </script>';
    }
}
?>