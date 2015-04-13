<?php
/*********************** Information *************************\
| $HeadURL$
| 
| Author: Jo2003
|
| Begin: 8/10/2010 / 4:48p
| 
| Last edited by: $Author$
| 
| $Id$
\*************************************************************/

// include kartinaAPI class instance ...
require_once(dirname(__FILE__)."/_kartina_auth.php.inc");
require_once(dirname(__FILE__)."/_timezones.php.inc");

// define column count ...
define (CHANCOLS, 8);
define (FAVCOLS, 4);

///////////////////////////////////////////////////////////////////////////////
//                            function section                               //
///////////////////////////////////////////////////////////////////////////////

/* -----------------------------------------------------------------\
|  Method: makeHeader
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: print html header
|
|  Parameters: optional title
|
|  Returns: --
\----------------------------------------------------------------- */
function makeHeader($title = "")
{
   header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
   header("Expires: Sat, 01 Jan 2000 05:00:00 GMT");
   
   echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN'\n"
       ."   'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n" 
       ."<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>\n" 
       ."<head>\n" 
       ."<meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n" 
       ."<title>".$title."</title>\n" 
       ."<style type='text/css'>\n" 
       ."<!--\n"
       ."   body {font-family: Verdana, Tahoma, Arial, sans-serif; font-size: 11px; margin: 0px; padding: 0px; text-align: left; color: #3A3A3A; background-color: #F4F4F4}\n"
       ."   table {width: 10%; background-color:#ddd;color:white;padding:0px;border:1px outset}\n"
       ."  .timetab {background-color:#ddd;color:#333;padding:0px;border:1px outset;}\n"
       ."  .navitab {background-color:#333333; color:white; padding:0px; border: 1px outset;}\n"
       ."  .tdnavitab {background-color:#ddd; color:black; padding:5px; border: 0px;}\n"
       ."  .favtab  {background-color:#333333; color:white; padding:0px; border: 1px outset;}\n"
       ."  .chantab {background-color:#333333; color:white; padding:0px; border: 1px outset;}\n"
       ."   img {border: 0px;}\n"
       ."   td {width:50%;text-align:center;vertical-align:middle;border:0px;padding:2px}\n"
       ."   th {background-color:#333333;color:white; text-align: left;}\n"
       ."  .timetab td {text-align: left;}\n"
       ."   td.row {background-color:white;color:black;}\n"
       ."   a:link, a:visited, a:active { text-decoration: underline; color: #444444;}\n"
       ."   a:hover { text-decoration: underline; color: #0482FE;}\n"
       ."-->\n"
       ."</style>\n" 
       ."</head>\n"
       ."<body>\n";
}

/* -----------------------------------------------------------------\
|  Method: makeFooter
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: print html footer
|
|  Parameters: --
|
|  Returns: --
\----------------------------------------------------------------- */
function makeFooter()
{
   echo "</body>\n"
       ."</html>";
}

/* -----------------------------------------------------------------\
|  Method: makeAccForm
|  Begin: 8/27/2010 / 15:00
|  Author: Jo2003
|  Description: make account form
|
|  Parameters: --
|
|  Returns: --
\----------------------------------------------------------------- */
function makeAccForm()
{
   global $wdtvConf;
   echo "<h3>Картина.тв Аккаунт</h3>\n"
       ."<form name='accountform' action='".$_SERVER['PHP_SELF']."' method='post'>\n"
       ."<input type='hidden' name='act' value='setacc' />\n"
       ."Аккаунт:&nbsp;\n"
       ."<input type='text' name='acc' value='".$wdtvConf->getVal("KARTINA_ACCOUNT")."' />&nbsp;\n"
       ."Пароль:&nbsp;\n"
       ."<input type='password' name='passwd' value='".$wdtvConf->getVal("KARTINA_PASSWD")."' />&nbsp;\n"
       ."<input type='submit' value='Сохранять' />\n"
       ."</form>\n";

   // test test test ...
   echo "<br /> <br />\n"
       ."<h3>Папка для Записьи</h3>\n"
       ."<form name='folderform' action='".$_SERVER['PHP_SELF']."' method='post'>\n"
       ."<input type='hidden' name='act' value='setrecfolder' />\n"
       ."Папка:&nbsp;\n"
       ."<input type='text' name='folder' value='".$wdtvConf->getVal("KART_REC_FOLDER")."' />&nbsp;\n"
       ."<input type='submit' value='Сохранять' />\n"
       ."</form>\n";
}

/* -----------------------------------------------------------------\
|  Method: makeTZForm
|  Begin: 8/30/2010 / 12:00
|  Author: Jo2003
|  Description: create timezone / server form
|
|  Parameters: --
|
|  Returns: --
\----------------------------------------------------------------- */
function makeTZForm()
{
   global $wdtvConf;
   
   // default time zone ...
   $def_tz = $wdtvConf->getVal("TIMEZONE");
/*   
   // NTP value ...
   $ntp    = $wdtvConf->getVal("NTP");
   
   if (strtoupper($ntp) === "ON")
   {
      $ntp = 1;
   }
   else
   {
      $ntp = 0;
   }
   
   // IPUP value ...
   $ipup   = $wdtvConf->getVal("IPUP");
   
   if (strtoupper($ipup) === "ON")
   {
      $ipup = 1;
   }
   else
   {
      $ipup = 0;
   }
*/
   echo "<h3>Установка времени</h3>\n"
       ."<form name='accountform' action='".$_SERVER['PHP_SELF']."' method='post'>\n"
       ."<input type='hidden' name='act' value='settimestuff' />\n"
       ."<table class='timetab'>\n"
       ."<tr>\n"
       ."<td nowrap='nowrap'>Часовой пояс:</td>\n"
       ."<td><select name='timezone'>\n";


   $tz   = createZoneinfoArray();
   $notz = count($tz);
   
   for ($i = 0; $i < $notz; $i++)
   {
      $sel = ($tz[$i] === $def_tz) ? " selected='selected'" : "";
      echo "<option value='".$tz[$i]."'".$sel.">".$tz[$i]."</option>\n";
   }

   echo "</select></td>\n"
       ."</tr>\n"
       ."<tr>\n"
       ."<td nowrap='nowrap'>Сервер NTP:</td>\n"
       ."<td><input type='text' name='ntpsrv' value='".$wdtvConf->getVal("NTPSERVER")."' /></td>\n"
       ."</tr>\n"
/*
       ."<tr>\n"
       ."<td nowrap='nowrap'>Вкл. NTP:</td>\n"
       ."<td>\n"
       ."нет <input type='radio' name='ntp' value='OFF' ".(($ntp) ? "" : "checked='checked' ")."/>\n"
       ."да <input type='radio' name='ntp' value='ON' ".(($ntp) ? "checked='checked' " : "")."/>\n"
       ."</td>\n"
       ."</tr>\n"
       ."<tr>\n"
       ."<td nowrap='nowrap'>Вкл. IPUP:</td>\n"
       ."<td>\n"
       ."нет <input type='radio' name='ipup' value='OFF' ".(($ipup) ? "" : "checked='checked' ")."/>\n"
       ."да <input type='radio' name='ipup' value='ON' ".(($ipup) ? "checked='checked' " : "")."/>\n"
       ."</td>\n"
       ."</tr>\n" 
*/
       ."<tr>\n"
       ."<td colspan='2'>*Требуется перезагрузка WDTV</td>\n"
       ."</tr>\n"
       ."<tr>\n"
       ."<td colspan='2'><input type='submit' value='Сохранять' /></td>\n"
       ."</tr>\n"
       ."</table>\n"
       ."</form>\n";
}

/* -----------------------------------------------------------------\
|  Method: makeFavTab
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: create favotites table
|
|  Parameters: favorites in XML, channels in XML
|
|  Returns: --
\----------------------------------------------------------------- */
function makeFavTab($favXml, $chanXml)
{
   $colcount      = 1;
   $domFavList    = new DOMDocument();
   $domChanList   = new DOMDocument();

   $domFavList->loadXML($favXml);
   $domChanList->loadXML($chanXml);

   $xpfav  = new DOMXpath($domFavList);
   $xpchan = new DOMXpath($domChanList);

   $favarray  = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
   $favorites = $xpfav->query("/response/favorites/item/channel_id");

   echo "<table class='favtab' border='0' cellspacing='1' cellpadding='1'>\n"
      ."<colgroup span='".FAVCOLS."' />\n"
      ."<tr><th colspan='".FAVCOLS."'>Фавориты</th></tr>\n";

   for ($i = 0; $i < count($favarray); $i ++)
   {
      if (($colcount % FAVCOLS) === 1)
      {
         echo "<tr>\n";
      }
      
      // check if this place is used ...
      $favitem  = $xpfav->query("/response/favorites/item[place='".$favarray[$i]."']");
      
      if ($favitem->length)
      {
         $cid      = $xpfav->query("channel_id", $favitem->item(0))->item(0)->nodeValue;
         $place    = $xpfav->query("place", $favitem->item(0))->item(0)->nodeValue;

         $chanitem = $xpchan->query("/response/groups/item/channels/item[id='".$cid."']");
         $chan     = $chanitem->item(0); // there is only one such item ...
         
         $icon     = $xpchan->query("icon", $chan)->item(0)->nodeValue;
         $name     = $xpchan->query("name", $chan)->item(0)->nodeValue;
         
         echo "<td><a href='".$_SERVER['PHP_SELF']."?act=fav&amp;del=".$place."'>"
             ."<img src='".KARTINA_HOST.$icon."' alt='channel icon' title='удалить ".$name." из фаворитов' />"
             ."</a></td>\n";
      }
      else
      {
         echo "<td><img src='".LOC_IMG_PATH."/help.png' alt='empty favorite' title='пустое место для фаворита' />"
             ."</td>\n";
      }

          
      if (($colcount % FAVCOLS) === 0)
      {
         echo "</tr>\n";
      }

      $colcount ++;
   }
   
   $finalcols = FAVCOLS - (($colcount - 1) % FAVCOLS);

   if ($finalcols != FAVCOLS)
   {
      echo "<td colspan='".$finalcols."'>&nbsp;</td>\n"
          ."</tr>\n";
   }
   echo "</table>\n";
}

/* -----------------------------------------------------------------\
|  Method: makeChanTab
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: create channel table
|
|  Parameters: favorites in XML, channels in XML
|
|  Returns: --
\----------------------------------------------------------------- */
function makeChanTab ($favXml, $chanXml)
{
   $colcount      = 1;
   $domFavList    = new DOMDocument();
   $domChanList   = new DOMDocument();
   
   $domFavList->loadXML($favXml);
   $domChanList->loadXML($chanXml);
   
   $xpfav  = new DOMXpath($domFavList);
   $xpchan = new DOMXpath($domChanList);
   
   $channels = $xpchan->query("/response/groups/item/channels/item/id");
   $names    = $xpchan->query("/response/groups/item/channels/item/name");
   $icons    = $xpchan->query("/response/groups/item/channels/item/icon");
   
   $chancount  = $channels->length;
   
   if ($chancount > 0)
   {
      echo "<table class='chantab' border='0' cellspacing='1' cellpadding='1'>\n"
          ."<colgroup span='".CHANCOLS."' />\n"
          ."<tr><th colspan='".CHANCOLS."'>Список каналов</th></tr>\n";
   }

   for ($i = 0; $i < $chancount; $i ++)
   {
      $cid      = $channels->item($i)->nodeValue;
      $icon     = $icons->item($i)->nodeValue;
      $name     = $names->item($i)->nodeValue;
      
      // is channel there as favorite ... ?
      $fav      = $xpfav->query("/response/favorites/item[channel_id='".$cid."']")->length;
      
      if (!$fav)
      {
         if (($colcount % CHANCOLS) === 1)
         {
            echo "<tr>\n";
         }
      
         // only display channel if it isn't already a favorite ...
         echo "<td><a href='".$_SERVER['PHP_SELF']."?act=fav&amp;add=".$cid."'>"
             ."<img src='".KARTINA_HOST.$icon."' alt='channel icon' title='добавить ".$name." в фавориты' />"
             ."</a></td>\n";

         if (($colcount % CHANCOLS) === 0)
         {
            echo "</tr>\n";
         }
         
         $colcount ++;
      }
   }
   
   if ($chancount > 0)
   {
      $finalcols = CHANCOLS - (($colcount - 1) % CHANCOLS);

      if ($finalcols != CHANCOLS)
      {
         echo "<td colspan='".$finalcols."'>&nbsp;</td>\n"
             ."</tr>\n";
      }
      
      echo "</table>\n";
   }
}

/* -----------------------------------------------------------------\
|  Method: makeNavi
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: create navigation table
|
|  Parameters: --
|
|  Returns: --
\----------------------------------------------------------------- */
function makeNavi()
{
   echo "<table class='navitab' border='0' cellpadding='1' cellspacing='1'>\n<tr>\n"
       ."<td class='tdnavitab' nowrap='nowrap'>[<a href='".$_SERVER['PHP_SELF']."'>Домой</a>]</td>\n"
       ."<td class='tdnavitab' nowrap='nowrap'>[<a href='".$_SERVER['PHP_SELF']."?act=fav'>Фавориты</a>]</td>\n"
       ."<td class='tdnavitab' nowrap='nowrap'>[<a href='".$_SERVER['PHP_SELF']."?act=delxml'>Удалить файлы</a>]</td>\n"
       ."<td class='tdnavitab' nowrap='nowrap'>[<a href='".$_SERVER['PHP_SELF']."?act=acc'>Картина.тв Аккаунт</a>]</td>\n"
       ."<td class='tdnavitab' nowrap='nowrap'>[<a href='".$_SERVER['PHP_SELF']."?act=timestuff'>Установка Времени</a>]</td>\n"
       ."</tr>\n</table>\n";
}

///////////////////////////////////////////////////////////////////////////////
//                            request handling                               //
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['act']))
{
   // favorite request ...
   if ($_GET['act'] === "fav")
   {
      // delete favorite ...
      if (isset($_GET['del']))
      {
         $kartAPI->setFavorite($_GET['del'], 0);
         header("Location: ".$_SERVER['PHP_SELF']."?act=fav");
      }
      
      // add favorite ...
      if (isset($_GET['add']))
      {
         $favs     = $kartAPI->getFavorites();
         $emptyfav = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
         
         for ($i = 0; $i < count($favs); $i ++)
         {
            $tmpfav = array();
            for ($j = 0; $j < count($emptyfav); $j++)
            {
               if ($favs[$i]['place'] != $emptyfav[$j])
               {
                  $tmpfav[] = $emptyfav[$j]; 
               }
            }
            $emptyfav = $tmpfav;
         }
         
         if (isset($emptyfav[0]))
         {
            $kartAPI->setFavorite($emptyfav[0], $_GET['add']);
            header("Location: ".$_SERVER['PHP_SELF']."?act=fav");
         }
      }
   }
   
   // delete XML request ...
   if ($_GET['act'] === "delxml")
   {
      /*
      @unlink(KARTCHANLIST);
      @unlink(KARTFAVLIST);
      */
      
      // delete cookie as well ...
      @unlink(COOKIE_FILE);
      header("Location: ".$_SERVER['PHP_SELF']);
   }
}

if (isset($_POST['act']))
{
   // save kartina account data ...
   if ($_POST['act'] === "setacc")
   {
      if (isset($_POST['acc']) && isset($_POST['passwd']))
      {
         if (($_POST['acc'] != "") && ($_POST['passwd'] != ""))
         {
            // save new kartina account config ...
            $wdtvConf->writeConf("KARTINA_ACCOUNT", $_POST['acc']);
            $wdtvConf->writeConf("KARTINA_PASSWD", $_POST['passwd']);
/*            
            // delete xml files because they may change due to account change ...
            @unlink(KARTCHANLIST);
            @unlink(KARTFAVLIST);
*/            
            // delete cookie file as well ...
            @unlink(COOKIE_FILE);
            
            // make sure kartina api is reloaded ...
            $kartAPI = NULL;
            
            // reload page ...
            header("Location: ".$_SERVER['PHP_SELF']);
         }
      }
   }
   else if($_POST['act'] === "settimestuff") // set time zone and time server ...
   {
      if (isset($_POST['timezone']) && isset($_POST['ntpsrv']))
      {
         if (($_POST['timezone'] != "") && ($_POST['ntpsrv'] != ""))
         {
            // save new timer server and time zone values ...
            $wdtvConf->writeConf("NTPSERVER", $_POST['ntpsrv']);
            $wdtvConf->writeConf("TIMEZONE", $_POST['timezone']);
/*            
            // save ntp and ipup stuff ...
            $wdtvConf->writeConf("NTP", $_POST['ntp']);
            $wdtvConf->writeConf("IPUP", $_POST['ipup']);
*/            
            // reload page ...
            header("Location: ".$_SERVER['PHP_SELF']);
         }
      }
   }
   else if($_POST['act'] === "setrecfolder") // set time zone and time server ...
   {
      if (isset($_POST['folder']))
      {
         // save folder ...
         $wdtvConf->writeConf("KART_REC_FOLDER", $_POST['folder']);
      }
   }
}

// create document header ...
makeHeader(isset($_GET['act']) ? $_GET['act'] : "Установка");

// show navi bar ...
makeNavi();

// show fav tables ...
if (isset($_GET['act']))
{
   if ($_GET['act'] === "fav")
   {
      if ((($chanXml = $kartAPI->getChannelListXml()) !== FALSE)
         && (($favXml = $kartAPI->getFavoritesXML()) !== FALSE))
      {
         echo "<table>\n<tr>\n<td style='vertical-align: top;'>\n";
         makeFavTab($favXml, $chanXml);
         echo "</td>\n<td>\n";
         makeChanTab($favXml, $chanXml);
         echo "</td>\n</tr>\n</table>\n";
      }
   }
   else if($_GET['act'] === "acc")
   {
      makeAccForm();
   }
   else if($_GET['act'] === "timestuff")
   {
      makeTZForm();
   }
}
else
{
   echo "Kartina Config Panel: Choose from actions above!";
}

// close document ...
makeFooter();
?>
