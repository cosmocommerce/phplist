<div align="center">
  <table class="about" border="1" cellspacing="5" cellpadding="5">
  <tr>
    <td colspan="2" class="abouthead"><?php echo NAME?></td>
  </tr>
  <tr>
    <td class="poweredby" valign="top">
      Powered by <a href="http://www.phplist.com" target="_blank">phplist</a>, version <?php echo VERSION?> <a href="http://www.phplist.com" target="_blank"><br/><br/>
      <img src="../images/power-phplist.png" alt="" width="70" height="30" border="0" /></a>
    </td>
    <td class="top">
    phplist is licensed with the <a href="http://www.gnu.org/copyleft" target="_blank">GNU Public License (GPL)</a>.<br/>
    Copyright &copy; 2000-<?php echo date('Y')?> <a href="http://phplist.com" target="_blank">phpList Ltd.</a><br/><br/>
    <h3><?php echo $GLOBALS['I18N']->get('Developers')?>:</h3>
    <ul>
      <li>Michiel Dethmers, phpList Ltd</li>
    </ul>
    <h3><?php echo $GLOBALS['I18N']->get('Contributors')?></h3>
    <ul>
      <li>H2B2, Forum Moderator, Mantis Manager</li>
      <li><a href="http://dragonrider.co.uk/phplist">Dragonrider</a>, Forum Moderator</li>
      <li><a href="http://www.dcameron.me.uk/phplist">Duncan Cameron</a>, Forum Moderator</li>
      
    </ul>
    <h3><?php echo $GLOBALS['I18N']->get('Design')?>:</h3>
    <ul>
    <li>Tarek Djebali</li>
    </ul>
    <h3><?php echo $GLOBALS['I18N']->get('Design implementation')?>:</h3>
    <ul>
    <li>Tarek Djebali</li>
    <li><a href="http://www.marielazarate.com.ar">Mariela Zárate</a></li>
    </ul>
    <h3><a href="http://docs.phplist.com" target="_blank"><?php echo $GLOBALS['I18N']->get('Documentation')?></a></h3>
    <ul>
      <li>Yan Brailowsky</li>
      <li>Pascal Van Hecke</li>
    </ul>
    <h3><?php echo $GLOBALS['I18N']->get('Translations') ?></h3>
    <ul>
      <li><a href="http://www.babels.org" target="_blank">Babels</a></li>
      <?php echo $GLOBALS['I18N']->get('credits for this translation') ?>
    </ul>
    <p>
        <?php echo $GLOBALS['I18N']->get('The developers wish to thank the many contributors to this system, who have helped out with bug reports, suggestions, donations, feature requests, sponsoring, translations and many other contributions.')?>
    </p>
    <b><?php echo $GLOBALS['I18N']->get('Portions of the system include')?></b>
    <ul>
      <li><a href="http://www.webbler.net" target="_blank">Webbler</a> code, by <a href="http://phplist.com" target="_blank">Michiel Dethmers</a></li>
      <li><a href="http://www.fckeditor.net/" target="_blank">FCKeditor</a>, by Frederico Caldeira Knabben and team</li>
      <li>the <a href="https://code.google.com/a/apache-extras.org/p/phpmailer/" target="_blank">phpmailer</a> class </li>
      <li>the <a href="http://jquery.com">jQuery</a> Javascript library</li> by the <a href="http://jquery.org/team">jQuery Team</a>
    </ul>
    </td>
    </tr>
   <?php 
   $pluginsHTML = '
    <tr>
    <td width="50" valign="top">'. $GLOBALS["I18N"]->get('Plugins'). '</td>
    <td valign="top">
      <ul class="aboutplugins">
';
   // list plugins and allow them to add details
$pg = '';
if (isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins']) && sizeof($GLOBALS['plugins'])) {
  foreach ($GLOBALS['plugins'] as $pluginName=>$plugin) {
    $pg .= '<li><strong>' . $plugin->name . '</strong> version ' . $plugin->version;
    if ( $plugin->authors ){
      $pg .= ' <span class="pluginauthor">by ' . $plugin->authors . '</span>'; 
    }
    if ( $plugin->displayAbout() ){
      $pg .= ' <span class="pluginabout"' . $plugin->displayAbout(). '</span>';
    }
    $pg .= '</li>';
  } 
    $pluginsHTML .= $pg . '
          </ul>
        </td>
      </tr>
  ';
  if ($pg != "") echo $pluginsHTML;
}
?>
</table>
</div>


