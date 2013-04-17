<?php
require_once dirname(__FILE__).'/accesscheck.php';
/*

Languages, countries, and the charsets typically used for them
http://www.w3.org/International/O-charset-lang.html

*/
## this array is now automatically build from the file system using the
## language_info file in each subdirectory of /locale/
## and further on, from the XML data of the translation site
$LANGUAGES = array(
"nl"=> array("Dutch ","UTF-8"," UTF-8, windows-1252 "),
"de" => array("Deutsch ","UTF-8","UTF-8, windows-1252 "),
"en" => array("English ","UTF-8","UTF-8, windows-1252 "),
"es"=>array("espa&ntilde;ol","UTF-8","UTF-8, windows-1252"),
#"fa" => array('Persian','utf-8','utf-8'),
"fr"=>array("fran&ccedil;ais ","UTF-8","UTF-8, windows-1252 "),
"pl"=>array("Polish ","UTF-8","UTF-8"),
"pt_BR"=>array("portugu&ecirc;s ","UTF-8","UTF-8, windows-1252"),
"zh_TW" => array("Traditional Chinese","utf-8","utf-8"),
'zh_CN' => array('Simplified Chinese',"utf-8","utf-8"),
"vi" => array("Vietnamese","utf-8","utf-8"),
);

## pick up languages from the lan directory
$landir = dirname(__FILE__).'/locale/';
$d = opendir($landir);
while ($lancode = readdir($d)) {
#  print "<br/>".$lancode;
  if (!in_array($landir,array_keys($LANGUAGES)) && is_dir($landir.'/'.$lancode) && is_file($landir.'/'.$lancode.'/language_info')) {
    $lan_info = file_get_contents($landir.'/'.$lancode.'/language_info');
    $lines = explode("\n",$lan_info);
    $lan = array();
    foreach ($lines as $line) {
      // use utf8 matching
      if (preg_match('/(\w+)=([\p{L}\p{N}&; \-\(\)]+)/u',$line,$regs)) {
#      if (preg_match('/(\w+)=([\w&; \-\(\)]+)/',$line,$regs)) {
#      if (preg_match('/(\w+)=(.+)/',$line,$regs)) {
        $lan[$regs[1]] = $regs[2];
      }
    }
    if (!isset($lan['gettext'])) $lan['gettext'] = $lancode;
    if (!empty($lan['name']) && !empty($lan['charset'])) {
      $LANGUAGES[$lancode] = array($lan['name'],$lan['charset'],$lan['charset'],$lan['gettext']);
    }
    
#    print '<br/>'.$landir.'/'.$lancode;
  }
}
## pick up other languages from DB
$req = Sql_Query(sprintf('select lan,translation from %s where 
  original = "language-name" and lan not in ("%s")',$GLOBALS['tables']['i18n'], join('","',array_keys($LANGUAGES))));
while ($row = Sql_Fetch_Assoc($req)) {
  $LANGUAGES[$row['lan']] = array($row['translation'],'UTF-8','UTF-8',$row['lan']);
}

function lanSort($a,$b) {
  return strcmp(strtolower($a[0]),strtolower($b[0]));
}
uasort($LANGUAGES,"lanSort");
#var_dump($LANGUAGES);

if (!empty($GLOBALS["SessionTableName"])) {
  require_once dirname(__FILE__).'/sessionlib.php';
}
@session_start();

if (isset($_POST['setlanguage']) && !empty($_POST['setlanguage']) && is_array($LANGUAGES[$_POST['setlanguage']])) {
  $_SESSION['adminlanguage'] = array(
    "info" => $_POST['setlanguage'],
    "iso" => $_POST['setlanguage'],
    "charset" => $LANGUAGES[$_POST['setlanguage']][1],
  );
#  var_dump($_SESSION['adminlanguage'] );
}

/*
if (!empty($_SESSION['show_translation_colours'])) {
  $GLOBALS['pageheader']['translationtools'] = '
    <script type="text/javascript" src="js/jquery.contextMenu.js"></script>
    <link rel="stylesheet" href="js/jquery.contextMenu.css" />
    <ul id="translationMenu" class="contextMenu">
    <li class="translate">
        <a href="#translate">Translate</a>
    </li>
    <li class="quit separator">
        <a href="#quit">Quit</a>
    </li>
</ul>
  <script type="text/javascript">
  $(document).ready(function(){
    $(".translate").contextMenu({
        menu: "translationMenu"
    },
    function(action, el, pos) {
      alert(
          "Action: " + action + "\n\n" +
          "Element ID: " + $(el).attr("id") + "\n\n" +
          "X: " + pos.x + "  Y: " + pos.y + " (relative to element)\n\n" +
          "X: " + pos.docX + "  Y: " + pos.docY+ " (relative to document)"
      );
    });
  });
  </script>

  ';
}
*/

if (!isset($_SESSION['adminlanguage']) || !is_array($_SESSION['adminlanguage'])) {
  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $accept_lan = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
  } else {
    $accept_lan = array('en'); # @@@ maybe make configurable?
  }
  $detectlan = '';

  /* @@@TODO
   * we need a mapping from Accept-Language to gettext, see below
   *
   * eg nl-be becomes nl_BE
   *
   * currently "nl-be" will become "nl" and not "nl_BE";
   */
  
  foreach ($accept_lan as $lan) {
    if (!$detectlan) {
      if (preg_match('/^([\w-]+)/',$lan,$regs)) {
        $code = $regs[1];
        if (isset($LANGUAGES[$code])) {
          $detectlan = $code;
        } elseif (strpos($code,'-') !== false) {
          list($language,$country) = explode('-',$code);
          if (isset($LANGUAGES[$language])) {
            $detectlan = $language;
          }
        }
      }
    }
  }
  if (!$detectlan) {
    $detectlan = 'en';
  }

  $_SESSION['adminlanguage'] = array(
    "info" => $detectlan,
    "iso" => $detectlan,
    "charset" => $LANGUAGES[$detectlan][1],
  );
}

## this interferes with the frontend if an admin is logged in. 
## better split the frontend and backend charSets at some point
#if (!isset($GLOBALS['strCharSet'])) {
  $GLOBALS['strCharSet'] = $_SESSION['adminlanguage']['charset'];
#
#var_dump($_SESSION['adminlanguage']);
#print '<h1>'. $GLOBALS['strCharSet'].'</h1>';

# internationalisation (I18N)

class phplist_I18N {
  public $defaultlanguage = 'en';
  public $language = 'en';
  public $basedir = '';
  private $hasGettext = false;
  private $hasDB = false;
  private $lan = array();

  function phplist_I18N() {
    $this->basedir = dirname(__FILE__).'/locale/';
  #  if (isset($_SESSION['adminlanguage']) && is_dir($this->basedir.$_SESSION['adminlanguage']['iso'])) {
    if (isset($_SESSION['adminlanguage']) && isset($GLOBALS['LANGUAGES'][$_SESSION['adminlanguage']['iso']])) {
      $this->language = $_SESSION['adminlanguage']['iso'];
    } else {
#      logEvent('Invalid language '.$_SESSION['adminlanguage']['iso']);
#      print "Not set or found: ".$_SESSION['adminlanguage']['iso'];
      unset($_SESSION['adminlanguage']);
      $this->language = 'en';
#      exit;
    }
    if (function_exists('gettext')) {
      $this->hasGettext = true;
    }
    if (Sql_Check_For_Table('i18n')) {
      $this->hasDB = true;
    }
    if (isset($_GET['origpage']) && !empty($_GET['ajaxed'])) { ## used in ajaxed requests
      $page = basename($_GET["origpage"]);
    } elseif (isset($_GET["page"])) {
      $page = basename($_GET["page"]);
    } else {
      $page = "home";
    }
    ## as we're including things, let's make sure it's clean
    $page = preg_replace('/\W/','',$page);

    if (!empty($_GET['pi'])) {
      $plugin_languagedir = $this->getPluginBasedir();
      if (is_dir($plugin_languagedir)) {
         $this->basedir = $plugin_languagedir;
         if (isset($GLOBALS['plugins'][$_GET['pi']])) {
           $plugin = $GLOBALS['plugins'][$_GET['pi']];
           if ($plugin->enabled && $plugin->needI18N && $plugin->i18nLanguageDir() ) {
             $this->basedir = $plugin->i18nLanguageDir();
           }
         }
      }
    }

    $lan = array();
    
    if (is_file($this->basedir.$this->language.'/'.$page.'.php')) {
      @include $this->basedir.$this->language.'/'.$page.'.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $this->basedir.$this->defaultlanguage.'/'.$page.'.php';
    }
    $this->lan = $lan;
    $lan = array();

    if (is_file($this->basedir.$this->language.'/common.php')) {
      @include $this->basedir.$this->language.'/common.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $this->basedir.$this->defaultlanguage.'/common.php';
    }
    $this->lan += $lan;
    $lan = array();

    if (is_file($this->basedir.$this->language.'/frontend.php')) {
      @include $this->basedir.$this->language.'/frontend.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $this->basedir.$this->defaultlanguage.'/frontend.php';
    }
    $this->lan += $lan;

  }

  function gettext($text) {
    bindtextdomain('phplist', './locale');
    textdomain('phplist');

    /* gettext is a bit messy, at least on my Ubuntu 10.10 machine
     *
     * if eg language is "nl" it won't find it. It'll need to be "nl_NL";
     * also the Ubuntu system needs to have the language installed, even if phpList has it
     * it won't find it, if it's not on the system
     * 
     * So, to e.g. get "nl" gettext support in phpList (on ubuntu, but presumably other linuxes), you'd have to do
     * cd /usr/share/locales
     * ./install-language-pack nl_NL
     * dpkg-reconfigure locales
     *
     * but when you use "nl_NL", the language .mo can still be in "nl".
     * However, it needs "nl/LC_MESSAGES/phplist.mo s, put a symlink LC_MESSAGES to itself
     *
     * the "utf-8" strangely enough needs to be added but can be spelled all kinds
     * of ways, eg "UTF8", "utf-8"
     *
     *
     * AND then of course the lovely Accept-Language vs gettext
     * https://bugs.php.net/bug.php?id=25051
     *
     * Accept-Language is lowercase and with - and gettext is country uppercase and with underscore
     * 
     * More ppl have come across that: http://grep.be/articles/php-accept
     * 
    */

    ## so, to get the mapping from "nl" to "nl_NL", use a gettext map in the related directory
    if (is_file(dirname(__FILE__).'/locale/'.$this->language.'/gettext_code')) {
      $lan_map = file_get_contents(dirname(__FILE__).'/locale/'.$this->language.'/gettext_code');
      $lan_map = trim($lan_map);
    } else {
      ## try to do "fr_FR", or "de_DE", might work in most cases
      ## hmm, not for eg fa_IR or zh_CN so they'll need the above file
      # http://www.gnu.org/software/gettext/manual/gettext.html#Language-Codes      
      $lan_map = $this->language.'_'.strtoupper($this->language);
    }

    putenv("LANGUAGE=".$lan_map.'.utf-8'); 
    setlocale(LC_ALL, $lan_map.'.utf-8');
    bind_textdomain_codeset('phplist', 'UTF-8');
    $gt = gettext($text);
    if ($gt && $gt != $text) return $gt;
  }

  function databaseTranslation($text) {
    if (!$this->hasDB) return '';
    $tr = Sql_Fetch_Row_Query(sprintf('select translation from '.$GLOBALS['tables']['i18n'].' where original = "%s" and lan = "%s"',
      sql_escape($text),$this->language),1);
    return $tr[0];
  }

  function pageTitle($page) {
    ## try gettext and otherwise continue
    if ($this->hasGettext) {
      $gettext = $this->gettext($page);
      if (!empty($gettext)) {
        return $gettext;
      }
    }
    $page_title = '';
    if (is_file(dirname(__FILE__).'/locale/'.$this->language.'/pagetitles.php')) {
      include dirname(__FILE__).'/locale/'.$this->language.'/pagetitles.php';
    } elseif (is_file(dirname(__FILE__).'/lan/'.$this->language.'/pagetitles.php')) {
      include dirname(__FILE__).'/lan/'.$this->language.'/pagetitles.php';
    }
    if (preg_match('/pi=([\w]+)/',$page,$regs)) {
      ## @@TODO call plugin to ask for title
      if (isset($GLOBALS['plugins'][$regs[1]])) {
        $title = $GLOBALS['plugins'][$regs[1]]->pageTitle($page);
      } else {
        $title = $regs[1].' - '.$page;
      }

    } elseif (!empty($page_title)) {
      $title = $page_title;
    } else {
      $title = $page;
    }
    return $title;
  }

  function formatText($text) {
    # we've decided to spell phplist with uc L
    $text = str_ireplace('PHPlist','phpList',$text);

    if (isset($GLOBALS["developer_email"])) {
      if (!empty($_SESSION['show_translation_colours'])) {
        return '<span style="color:#A704FF">'.str_replace("\n","",$text).'</span>';
      }
#       return 'TE'.$text.'XT';
    }
#    return '<span class="translateabletext">'.str_replace("\n","",$text).'</span>';
    return str_replace("\n","",$text);
  }

  function missingText($text) {
    if (isset($GLOBALS["developer_email"])) {
      if (isset($_GET['page'])) {
        $page = $_GET["page"];
      } else {
        $page = 'main page';
      }
      $pl = $prefix = '';
      if (!empty($_GET['pi'])) {
        $pl = $_GET['pi'];
        $pl = preg_replace('/\W/','',$pl);
        $prefix = $pl.'_';
      }

      $msg = '

      Undefined text reference in page '.$page.'

      '.$text;

      #sendMail($GLOBALS["developer_email"],"phplist dev, missing text",$msg);
      $line = "'".str_replace("'","\'",$text)."' => '".str_replace("'","\'",$text)."',";
#      if (is_file($this->basedir.'/en/'.$page.'.php') && $_SESSION['adminlanguage']['iso'] == 'en') {
      if (empty($prefix) && $_SESSION['adminlanguage']['iso'] == 'en') {
        $this->appendText($this->basedir.'/en/'.$page.'.php',$line);
      } else {
        $this->appendText('/tmp/'.$prefix.$page.'.php',$line);
      }

      if (!empty($_SESSION['show_translation_colours'])) {
        return '<span style="color: #FF1717">'.$text.'</span>';#MISSING TEXT
      }
    }
    return $text;
  }

  function appendText($file,$text) {
    return;
    $filecontents = '';
    if (is_file($file)) {
      $filecontents = file_get_contents($file);
    } else {
      $filecontents = '<?php

$lan = array(

);

      ?>';
    }

#    print "<br/>Writing $text to $file";
    $filecontents = preg_replace("/\n/","@@NL@@",$filecontents);
    $filecontents = str_replace(');','  '.$text."\n);",$filecontents);
    $filecontents = str_replace("@@NL@@","\n",$filecontents);

    $dir = dirname($file);
    if (!is_writable($dir) || (is_file($file) && !is_writable($file))) {
      $newfile = basename($file);
      $file = '/tmp/'.$newfile;
    }

    file_put_contents($file,$filecontents);
  }

  function getPluginBasedir() {
    $pl = $_GET['pi'];
    $pl = preg_replace('/\W/','',$pl);
    $pluginroot = '';
    if (isset($GLOBALS['plugins'][$pl]) && is_object($GLOBALS['plugins'][$pl])) {
      $pluginroot = $GLOBALS['plugins'][$pl]->coderoot;
    }
    if (is_dir($pluginroot.'/lan/')) {
      return $pluginroot.'/lan/';
    } else {
      return $pluginroot.'/';
    }
  }
  
  function getTranslation($text,$page,$basedir) {

    ## try DB, as it will be the latest
    if ($this->hasDB) {
      $db_trans = $this->databaseTranslation($text);
      if (!empty($db_trans)) {
        return $this->formatText($db_trans);
      }
    }

    ## next try gettext, although before that works, it requires loads of setting up
    ## but who knows
    if ($this->hasGettext) {
      $gettext = $this->gettext($text);
      if (!empty($gettext)) {
        return $this->formatText($gettext);
      }
    }

    $lan = $this->lan;

    if (trim($text) == "") return "";
    if (strip_tags($text) == "") return $text;
    if (isset($lan[$text])) {
      return $this->formatText($lan[$text]);
    }
    if (isset($lan[strtolower($text)])) {
      return $this->formatText($lan[strtolower($text)]);
    }
    if (isset($lan[strtoupper($text)])) {
      return $this->formatText($lan[strtoupper($text)]);
    }
    
    return '';
  }
  
  
  function get($text) {
    if (trim($text) == "") return "";
    if (strip_tags($text) == "") return $text;
    $translation = '';
    
    $this->basedir = dirname(__FILE__).'/lan/';
    if (isset($_GET['origpage']) && !empty($_GET['ajaxed'])) { ## used in ajaxed requests
      $page = basename($_GET["origpage"]);
    } elseif (isset($_GET["page"])) {
      $page = basename($_GET["page"]);
    } else {
      $page = "home";
    }
    $page = preg_replace('/\W/','',$page);
      
    if (!empty($_GET['pi'])) {
      $plugin_languagedir = $this->getPluginBasedir();
      if (is_dir($plugin_languagedir)) {
        $translation = $this->getTranslation($text,$page,$plugin_languagedir);
      }
    }
    
    ## if a plugin did not return the translation, find it in core
    if (empty($translation)) {
      $translation = $this->getTranslation($text,$page,$this->basedir);
    }
  
 #   print $this->language.' '.$text.' '.$translation. '<br/>';
  
    # spelling mistake, retry with old spelling
    if ($text == 'over threshold, user marked unconfirmed' && empty($translation)) {
      return $this->get('over treshold, user marked unconfirmed');
    }
    
    if (!empty($translation)) {
      return $translation;
    } else {
      return $this->missingText($text);
    }
  }
}

function getTranslationUpdates() {
  ## @@@TODO add some more error handling
  $LU = false;
  $lan_update = fetchUrl(TRANSLATIONS_XML);
  if (!empty($lan_update)) {
    $LU = simplexml_load_string($lan_update);
  }
  return $LU;
}

$I18N = new phplist_I18N();

/* add a shortcut that seems common in other apps 
 * function s($text)
 * @param $text string the text to find
 * @params 2-n variable - parameters to pass on to the sprintf of the text
 * @return translated text with parameters filled in
 * 
 * 
 * eg s("This is a %s with a %d and a %0.2f","text",6,1.98765);
 * 
 * will look for the translation of the string and substitute the parameters
 *  
 **/

function s($text) {
  ## allow overloading with sprintf paramaters
  $translation = $GLOBALS['I18N']->get($text);

  if (func_num_args() > 1) {
    $args = func_get_args();
    array_shift($args);
    $translation = vsprintf($translation, $args);
  }
  return $translation;
}

function parsePo($translationUpdate) {
  $translation_lines = explode("\n",$translationUpdate);
  $original = '';
  $translation = '';
  $translations = array();
  foreach ($translation_lines as $line) {
    if (preg_match('/^msgid "(.*)"/',$line,$regs)) {
      $original = $regs[1];
    } elseif (preg_match('/^msgstr "(.*)"/',$line,$regs)) {
    #  $status .= '<br/>'.$original.' '.$regs[1];
      $translation = $regs[1];
    } elseif (preg_match('/"(.*)"/',$line,$regs)) {# && !empty($translation)) {
      ## wrapped to multiple lines
      $translation .= $regs[1];
    } elseif (preg_match('/^#/',$line) || preg_match('/^\s+$/',$line)) {
      $original = $translation = '';
    }
    if (!empty($original) && !empty($translation)) {
      $translations[$original] = $translation;
    }
  }
  return $translations;
}
