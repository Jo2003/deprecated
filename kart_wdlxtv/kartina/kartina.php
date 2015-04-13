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

require_once(dirname(__FILE__)."/_kartina_auth.php.inc");

/* -----------------------------------------------------------------\
|  Method: _pluginMain
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: plugin entry point 
|
|  Parameters: get request string
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginMain($prmQuery) 
{
   $queryData = array();
   $items     = array();
   simpleLog(__FUNCTION__."():".__LINE__." Raw query: ".$prmQuery);
   
   parse_str($prmQuery, $queryData);

   if (!isset($queryData['action']))
   {
      $items = _pluginCreateChannelGroupList();
   }
   else
   {
      simpleLog(__FUNCTION__."():".__LINE__." Action: ".$queryData['action']);
      if ($queryData['action'] === "favorites")
      {
         $items = _pluginCreateFavList();
      }
      else if ($queryData['action'] === "channels")
      {
         simpleLog(__FUNCTION__."():".__LINE__." Changrp: ".$queryData['changrp']);
         $items = _pluginCreateChannelList($queryData['changrp']);
      }
      else if ($queryData['action'] === "arch_main")
      {
         simpleLog(__FUNCTION__."():".__LINE__." ChanID: ".$queryData['cid']);
         $items = _pluginCreateArchMainFolder ($queryData['cid']);
      }
      else if ($queryData['action'] === "archive")
      {
         simpleLog(__FUNCTION__."():".__LINE__." ChanID: ".$queryData['cid']
                  ."; Day: ".$queryData['day']);
         $items = _pluginCreateArchiveEpg ($queryData['cid'], $queryData['day']);
      }
      else if ($queryData['action'] === "chooserecplay")
      {
         $gmt     = (isset($queryData['gmt']))      ? (integer)$queryData['gmt']      :    -1;
         $isVideo = (isset($queryData['is_video'])) ? (boolean)$queryData['is_video'] :  true;
         
         simpleLog(__FUNCTION__."():".__LINE__." ChanID: ".$queryData['cid']
                  ."; GMT: ".$gmt."; IsVideo: ".$isVideo);
                  
         $items = _pluginChooseRecOrPlay($queryData['cid'], $gmt, $isVideo);
      }
      else if ($queryData['action'] === "play_rewind")
      {
         simpleLog(__FUNCTION__."():".__LINE__." ChanID: ".$queryData['cid']
                  ."; GMT: ".$queryData['gmt']."; IsVideo: ".$queryData['is_video']);
                  
         $items = _pluginCreatePlayRewind($queryData['cid'], $queryData['gmt'], 
                                          $queryData['is_video']);
      }
      else if ($queryData['action'] === "vod_main")
      {
         $items = _pluginVodGenres();
      }
      else if ($queryData['action'] === "genre")
      {
         simpleLog(__FUNCTION__."():".__LINE__." Genre ID: ".$queryData['gid']);
         
         $items = _pluginGenreVideos ($queryData['gid']);
      }
      else if ($queryData['action'] === "video_main")
      {
         simpleLog(__FUNCTION__."():".__LINE__." Video ID: ".$queryData['vid']);
         
         $items = _pluginVideoDetails ($queryData['vid']);
      }
      else if ($queryData['action'] === "quick_view")
      {
         $items = _pluginQuickView();
      }
   }
   
   return $items;
}

/* -----------------------------------------------------------------\
|  Method: _pluginCreateChannelList
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: create channel list for given channel group 
|
|  Parameters: channel group
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginCreateChannelList($groupid) 
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   if (($channelList = $tmpKartAPI->getChannelListXml()) !== FALSE)
   {
      $retMediaItems = array();
      $dom = new DomDocument();
      $dom->loadXML($channelList);
      
      $xp        = new DOMXpath($dom);
      $groupitem = $xp->query("/response/groups/item[id='".$groupid."']");
      $group     = $groupitem->item(0); // there is only one such group ... 

      $channels  = $xp->query("channels/item/id", $group);
      $names     = $xp->query("channels/item/name", $group);
      $icons     = $xp->query("channels/item/icon", $group);

      $all = $channels->length;

      for ($i = 0; $i < $all; $i++)
      {
         // has archive ...
         $data       = array('action' => 'arch_main', 
                             'cid'    => $channels->item($i)->nodeValue);
                             
         $dataString = http_build_query($data, "", "&amp;");
         
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
            'dc:title'       => $names->item($i)->nodeValue,
            'upnp:class'     => 'object.container',
            'upnp:album_art' => KARTINA_HOST.$icons->item($i)->nodeValue
         );
      }
   }
   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginQuickView
|  Begin: 07.02.2011 / 15:00
|  Author: Jo2003
|  Description: create a channel list with all channels 
|
|  Parameters: --
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginQuickView ()
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   if (($channelList = $tmpKartAPI->getChannelListXml()) !== FALSE)
   {
      // play data array ...
      $play_data = array(
         'cid'      =>  0,     // channel id (will be updated below)
         'gmt'      => -1,     // live stream only
         'is_video' =>  true,  // video flag (will be updated below)
         'dorec'    =>  false  // record flag
      );
      
      $retMediaItems = array();
      $dom = new DomDocument();
      $dom->loadXML($channelList);
      
      $xp        = new DOMXpath($dom);

      $channels  = $xp->query("/response/groups/item/channels/item/id");
      $names     = $xp->query("/response/groups/item/channels/item/name");
      $icons     = $xp->query("/response/groups/item/channels/item/icon");
      $is_video  = $xp->query("/response/groups/item/channels/item/is_video");
      $progname  = $xp->query("/response/groups/item/channels/item/epg_progname");
      $start     = $xp->query("/response/groups/item/channels/item/epg_start");
      $end       = $xp->query("/response/groups/item/channels/item/epg_end");

      $all = $channels->length;

      for ($i = 0; $i < $all; $i++)
      {
         $epgname = $progname->item($i)->nodeValue;
         
         // cut between name and decription ...
         $cutpos = strpos($epgname, "\n");
   
         if ($cutpos !== false)
         {
            $epgname = substr($epgname, 0, $cutpos);
         }
      
         // build title ...
         $title = $names->item($i)->nodeValue
                 ." ".date("H:i", $start->item($i)->nodeValue)
                 ."-".date("H:i", $end->item($i)->nodeValue)
                 ." ".$epgname;
                 
         // is this video or audio ... ?
         $isVideo = ((integer)$is_video->item($i)->nodeValue === 1) ? true : false;
                 
         // fill in needed values into play array ...
         $play_data['cid']      = $channels->item($i)->nodeValue;
         $play_data['is_video'] = $isVideo;
      
         $play_data_query = http_build_query($play_data);
      
         // add play item ...
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($play_data_query)),
            'dc:title'       => $title,
            'upnp:class'     => ($isVideo) ? "object.item.videoitem" : "object.item.audioitem",
            'res'            => LOC_KARTINA_URL."/http-stream-recorder.php?".$play_data_query,
            'protocolInfo'   => "http-get:*:*:*",
            'upnp:album_art' => KARTINA_HOST.$icons->item($i)->nodeValue
         );
      }
   }
   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginVideoDetails
|  Begin: 16.12.2010 / 13:00
|  Author: Jo2003
|  Description: create video folder 
|
|  Parameters: --
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginVideoDetails ($vid)
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   
   // get genre array ...
   $video         = $tmpKartAPI->getVideoTracks ($vid);
   
   // create folders with genres ...
   for ($i = 0; $i < count($video['ids']); $i++)
   {
      $url = $tmpKartAPI->getVodUrl($video['ids'][$i]);
   
      // add play item ...
      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($url)),
         'dc:title'       => $video['name'].((count($video['ids']) > 1) ? " Част ".($i + 1) : ""),
         'upnp:class'     => "object.item.videoitem",
         'res'            => $url,
         'protocolInfo'   => VODPROTINFO,
         'upnp:album_art' => LOC_KARTINA_URL."/images/play.png"
      );
   }
   
   /////////////////////////////////////////////////////////////////////////////
   // description image ...

   // vod data array ...
   $vod_data = array ('vod_tid' => $vid);

   $vod_data_query = http_build_query($vod_data);

   // epg info image ...
   $retMediaItems[] = array (
      'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($vod_data_query)),
      'dc:title'       => "Информация",
      'upnp:class'     => "object.item.imageitem",
      'res'            => LOC_KARTINA_URL."/vodinfo.php?".$vod_data_query,
      'protocolInfo'   => "http-get:*:image/JPEG:DLNA.ORG_PN=JPEG_LRG",
      'resolution'     => "1920x1080",
      'colorDepth'     => 24
   );
   
   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginVodGenres
|  Begin: 16.12.2010 / 11:00
|  Author: Jo2003
|  Description: create folder with vod genres
|
|  Parameters: --
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginVodGenres()
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   
   // add "all" entry ...
   $data       = array('action' => 'genre',
                       'gid'    => -1);
                       
   $dataString = http_build_query($data, "", "&amp;");

   $retMediaItems[] = array (
      'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
      'dc:title'       => 'ВСЕ',
      'upnp:class'     => 'object.container',
      'upnp:album_art' => LOC_KARTINA_URL."/images/vod.png",
   );
   
   // get genre array ...
   $genres        = $tmpKartAPI->getVodGenres ();
   
   // create folders with genres ...
   for ($i = 0; $i < count($genres); $i++)
   {
      $data       = array('action' => 'genre',
                          'gid'    => $genres[$i]['id']);
                          
      $dataString = http_build_query($data, "", "&amp;");
   
      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
         'dc:title'       => $genres[$i]['name'],
         'upnp:class'     => 'object.container',
         'upnp:album_art' => LOC_KARTINA_URL."/images/vod.png",
      );
   }
   return $retMediaItems; 
}

/* -----------------------------------------------------------------\
|  Method: _pluginGenreVideos
|  Begin: 16.12.2010 / 12:30
|  Author: Jo2003
|  Description: list all videos related to genre
|
|  Parameters: genre id
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginGenreVideos($gid)
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   
   // get videos array ...
   $videos        = $tmpKartAPI->getGenreVideos ($gid);
   
   // create folders with genres ...
   for ($i = 0; $i < count($videos); $i++)
   {
      $data       = array('action' => 'video_main',
                          'vid'    => $videos[$i]['id']);
                          
      $dataString = http_build_query($data, "", "&amp;");
      
      $title = $videos[$i]['name']." ("
              .$videos[$i]['country']." "
              .$videos[$i]['year'].")";
   
      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
         'dc:title'       => $title,
         'upnp:class'     => 'object.container',
         'upnp:album_art' => KARTINA_HOST.$videos[$i]['img']
      );
   }
   
   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginCreateChannelGroupList
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: create channel group list with favorite folder 
|
|  Parameters: --
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginCreateChannelGroupList()
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   $dom           = new DomDocument();
   
   // first add favorites folder ...
   $data       = array('action' => 'favorites');
   
   $dataString = http_build_query($data, "", "&amp;");
   
   $retMediaItems[] = array (
      'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
      'dc:title'       => "Фавориты",
      'upnp:class'     => 'object.container',
      'upnp:album_art' => LOC_KARTINA_URL."/images/favorite.png",
   );
   
   // 2nd add VOD folder ...
   $data       = array('action' => 'vod_main');
   
   $dataString = http_build_query($data, "", "&amp;");
   
   $retMediaItems[] = array (
      'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
      'dc:title'       => "Видеотека",
      'upnp:class'     => 'object.container',
      'upnp:album_art' => LOC_KARTINA_URL."/images/vod.png",
   );
   
   // 3rd add quick view folder ...
   $data       = array('action' => 'quick_view');
   
   $dataString = http_build_query($data, "", "&amp;");
   
   $retMediaItems[] = array (
      'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
      'dc:title'       => "Live Streams",
      'upnp:class'     => 'object.container',
      'upnp:album_art' => LOC_KARTINA_URL."/images/play.png",
   );
   
   if (($channelList = $tmpKartAPI->getChannelListXml()) !== FALSE)
   {
      $dom->loadXML($channelList);
      
      $xp         = new DOMXpath($dom);
         
      $groups     = $xp->query("/response/groups/item/id");
      $names      = $xp->query("/response/groups/item/name");

      $all = $groups->length;

      for ($i = 0; $i < $all; $i++)
      {
         $data       = array('action'  => 'channels',
                             'changrp' => $groups->item($i)->nodeValue);
                             
         $dataString = http_build_query($data, "", "&amp;");
         
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
            'dc:title'       => $names->item($i)->nodeValue,
            'upnp:class'     => 'object.container',
            'upnp:album_art' => LOC_KARTINA_URL."/images/folder.png"
         );
      }
   }

   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginCreateFavList
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: create favorites list 
|
|  Parameters: --
|
|  Returns: array of items to be displayed
\----------------------------------------------------------------- */
function _pluginCreateFavList()
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   $domChanList   = new DOMDocument();
   
   if (($channelList = $tmpKartAPI->getChannelListXml()) !== FALSE)
   {
      $domChanList->loadXML($channelList);
      
      $xpchan    = new DOMXpath($domChanList);
      
      $favorites = $tmpKartAPI->getFavorites ();
      $favcount  = count($favorites);

      for ($i = 0; $i < $favcount; $i ++)
      {
         $cid      = $favorites[$i]['cid'];
         $chanitem = $xpchan->query("/response/groups/item/channels/item[id='".$cid."']");
         $chan     = $chanitem->item(0); // there is only one such item ...
         
         $icon     = $xpchan->query("icon", $chan)->item(0)->nodeValue;
         $name     = $xpchan->query("name", $chan)->item(0)->nodeValue; 

         $data       = array('action' => 'arch_main', 
                             'cid'    => $cid);
                             
         $dataString = http_build_query($data, "", "&amp;");
         
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
            'dc:title'       => $name,
            'upnp:class'     => 'object.container',
            'upnp:album_art' => KARTINA_HOST.$icon
         );
      }
   }
   
   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginCreateArchMainFolder
|  Begin: 10/5/2010 / 1:56p
|  Author: Jo2003
|  Description: create the archive main folder with live stream and 
|               day folders
|  Parameters: channel id
|
|  Returns: array of media items
\----------------------------------------------------------------- */
function _pluginCreateArchMainFolder ($cid)
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   
   $days = array(1 => "Понедельник",
                 2 => "Вторник",
                 3 => "Среда",
                 4 => "Четверг",
                 5 => "Пятница",
                 6 => "Суббота",
                 7 => "Воскресенье");

   // first item is always the live stream ...

   // get info / url from given channel ...
   $domChanList = new DOMDocument();
   
   if (($channelList = $tmpKartAPI->getChannelListXml()) !== FALSE)
   {
      $domChanList->loadXML($channelList);

      $xpchan   = new DOMXpath($domChanList);

      $chanitem = $xpchan->query("/response/groups/item/channels/item[id='".$cid."']");
      $chan     = $chanitem->item(0); // there is only one such item ...

      $icon     = $xpchan->query("icon", $chan)->item(0)->nodeValue;
      $epgname  = $xpchan->query("epg_progname", $chan)->item(0)->nodeValue;
      $epgstart = (integer)$xpchan->query("epg_start", $chan)->item(0)->nodeValue;    
      $epgend   = (integer)$xpchan->query("epg_end", $chan)->item(0)->nodeValue;
      $isvideo  = (integer)$xpchan->query("is_video", $chan)->item(0)->nodeValue;
      $hasarch  = (integer)$xpchan->query("have_archive", $chan)->item(0)->nodeValue;
   
      // cut between name and decription ...
      $cutpos = strpos($epgname, "\n");
   
      if ($cutpos !== false)
      {
         $epgname = substr($epgname, 0, $cutpos);
      }
   
      // build title ...
      $title    = "Сейчас: ".date("H:i", $epgstart)."-".date("H:i", $epgend)." ".$epgname;
   
      // prepare data to choose between rec and play ...
      $data     = array('action'   => 'chooserecplay',
                        'is_video' => $isvideo,
                        'cid'      => $cid);

      $dataString = http_build_query($data, "", "&amp;");
   
      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
         'dc:title'       => $title,
         'upnp:class'     => 'object.container',
         'upnp:album_art' => KARTINA_HOST.$icon
      );

      // 2nd entry will be todays entry ...
      $now   = $tmpKartAPI->getLastServerTime();
      $data  = array('action'  => 'archive',
                     'day'     => date ("dmy", $now),
                     'cid'     => $cid);

      $dataString = http_build_query($data, "", "&amp;");

      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
         'dc:title'       => 'Сегодня',
         'upnp:class'     => 'object.container',
         'upnp:album_art' => LOC_KARTINA_URL."/images/archive.png"
      );

      // make folders for all 14 days of the archive ... 
      $archstart = $now;
   
      if ($hasarch)
      {
         $archstart -= MAX_ARCH_DAYS * DAY_IN_SECONDS;
      }
   
      $epgend = $now + MAX_EPG_DAYS * DAY_IN_SECONDS;

      for ($i = $archstart; $i <= $epgend; $i += DAY_IN_SECONDS)
      {
         // today was already handled above ...
         if ($i != $now)
         {
            // first add favorites folder ...
            $data       = array('action'  => 'archive',
                                'day'     => date ("dmy", $i),
                                'cid'     => $cid);
   
            $dataString = http_build_query($data, "", "&amp;");
      
            $retMediaItems[] = array (
               'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
               'dc:title'       => $days[date("N", $i)]. ", " .date("d.m.Y", $i),
               'upnp:class'     => 'object.container',
               'upnp:album_art' => LOC_KARTINA_URL."/images/archive.png"
            );
         }
      }
   }
   
   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginCreateArchiveEpg
|  Begin: 10/5/2010 / 1:56p
|  Author: Jo2003
|  Description: create the archive epg for one channel / day
|               day folders
|  Parameters: channel id, day in form ddmmyy
|
|  Returns: array of media items
\----------------------------------------------------------------- */
function _pluginCreateArchiveEpg ($cid, $day)
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   // global $kartAPI;
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();

   $retMediaItems = array();

   $epg = $tmpKartAPI->getDayEpg($cid, $day);
   
   $all = count($epg);
   
   simpleLog(__FUNCTION__."():".__LINE__." We found ".$all." EPG entries...");
   
   for ($i = 0; $i < $all; $i ++)
   {
      // prepare data to choose between rec and play ...
      $data       = array('action'   => 'chooserecplay',
                          'gmt'      => $epg[$i]['timestamp'],
                          'is_video' => 1,
                          'cid'      => $cid);
                       
      $dataString = http_build_query($data, "", "&amp;");
      
      $tok = strtok($epg[$i]['programm'], "\n");

      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
         'dc:title'       => date("H:i" , $epg[$i]['timestamp']) . " - " . $tok,
         'upnp:class'     => 'object.container',
         'upnp:album_art' => LOC_KARTINA_URL."/images/play.png"
      );
   }

   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginChooseRecOrPlay
|  Begin: 18.10.2010 / 16:16
|  Author: Jo2003
|  Description: make folder with rec and play item  
|
|  Parameters: channel id, gmt, video flag
|
|  Returns: array of media items ...
\----------------------------------------------------------------- */
function _pluginChooseRecOrPlay ($cid, $gmt = -1, $isVideo = true)
{
   // Please note that all global variables
   // are wiped out! Therefore we have to instantiate
   // a local instance here .... 
   
   // don't break your head about login / logout at kartina!
   // we will load the cookie from file so no authentication
   // is needed here ... we also don't need username and PW ...
   $tmpKartAPI = new kartinaAPI();
   
   // load cookie ...
   $tmpKartAPI->loadCookie();
   
   $retMediaItems = array();
   
   if (($channelList = $tmpKartAPI->getChannelListXml()) !== FALSE)
   {
      // get channel info ...
      $domChanList = new DOMDocument();
      $domChanList->loadXML($channelList);

      $xpchan   = new DOMXpath($domChanList);

      $chanitem = $xpchan->query("/response/groups/item/channels/item[id='".$cid."']");
      $chan     = $chanitem->item(0); // there is only one such item ...
   
      $recname  = $xpchan->query("name", $chan)->item(0)->nodeValue;
      $hasarch  = (integer)$xpchan->query("have_archive", $chan)->item(0)->nodeValue;
   
      /////////////////////////////////////////////////////////////////////////////
      // add epg info first ...
   
      // epg data array ...
      $epg_data = array ('cid' => $cid,
                         'gmt' => $gmt);
   
      $epg_data_query = http_build_query($epg_data);
   
      // epg info image ...
      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($epg_data_query)),
         'dc:title'       => "Информация",
         'upnp:class'     => "object.item.imageitem",
         'res'            => LOC_KARTINA_URL."/epg2img.php?".$epg_data_query,
         'protocolInfo'   => "http-get:*:image/JPEG:DLNA.ORG_PN=JPEG_LRG",
         'resolution'     => "1920x1080",
         'colorDepth'     => 24
      );
   
      /////////////////////////////////////////////////////////////////////////////
      // play item ...
   
      // if we have an archive, we can jump backward and forward.
      // To make this accessable, create a new play folder ...
      if (($gmt != -1) && $hasarch && inArchive($gmt, $tmpKartAPI->getLastServerTime()))
      {
         $data       = array('action'   => 'play_rewind',
                             'gmt'      => $gmt,
                             'is_video' => $isVideo,
                             'cid'      => $cid);
                          
         simpleLog(__FUNCTION__."():".__LINE__." Add Play / Rewind Item (cid=".$cid
                  .", gmt=".$gmt.", is_video=".$isVideo.")");
                       
         $dataString = http_build_query($data, "", "&amp;");
      
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".$dataString,
            'dc:title'       => "Просмотр / Перемотка",
            'upnp:class'     => 'object.container',
            'upnp:album_art' => LOC_KARTINA_URL."/images/play.png"
         );
      }
      else if ($gmt == -1)
      {
         // live stream doesn't allow forward / backward jumping ...
         // play data array ...
         $play_data = array(
            'cid'      => $cid,     // channel id
            'gmt'      => $gmt,     // timestamp for archive
            'is_video' => $isVideo, // video flag
            'dorec'    => false     // record flag
         );
      
         simpleLog(__FUNCTION__."():".__LINE__." Add Play Item (cid=".$cid
                  .", gmt=".$gmt.", is_video=".$isVideo.", dorec=false)");
      
         $play_data_query = http_build_query($play_data);
      
         // add play item ...
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($play_data_query)),
            'dc:title'       => "Просмотр",
            'upnp:class'     => ($isVideo) ? "object.item.videoitem" : "object.item.audioitem",
            'res'            => LOC_KARTINA_URL."/http-stream-recorder.php?".$play_data_query,
            'protocolInfo'   => "http-get:*:*:*",
            'upnp:album_art' => LOC_KARTINA_URL."/images/play.png"
         );
      }

      /////////////////////////////////////////////////////////////////////////////
      // record entry ...
      if (($gmt == -1) || ($hasarch && inArchive($gmt, $tmpKartAPI->getLastServerTime())))
      {
         // replace some funky characters which could lead to problems ...
         $recname  = str_replace(" ", "_", $recname)."-";
         $recname  = str_replace("/", "-", $recname);
         $recname  = str_replace("\\", "", $recname);
         
         // add date ...
         $recname .= ($gmt === -1) ? date("d.m.y-H_i", $tmpKartAPI->getLastServerTime()) 
                        : date("d.m.y-H_i", $gmt);
         
         // rec data array ...
         $rec_data = array(
            'cid'      => $cid,     // channel id
            'gmt'      => $gmt,     // timestamp for archive
            'is_video' => $isVideo, // video flag
            'dorec'    => true,     // record flag
            'recfile'  => $recname  // record file name
         );
         
         simpleLog(__FUNCTION__."():".__LINE__." Add Rec Item (cid=".$cid
                  .", gmt=".$gmt.", is_video=".$isVideo.", dorec=true, "
                  ."recfile=".$recname.")");
         
         $rec_data_query = http_build_query($rec_data);
         
         // add record item ...
         $retMediaItems[] = array (
            'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($rec_data_query)),
            'dc:title'       => "Запись в &quot;".$recname.".ts&quot;",
            'upnp:class'     => ($isVideo) ? "object.item.videoitem" : "object.item.audioitem",
            'res'            => LOC_KARTINA_URL."/http-stream-recorder.php?".$rec_data_query,
            'protocolInfo'   => "http-get:*:*:*",
            'upnp:album_art' => LOC_KARTINA_URL."/images/record.png"
         );
      }
   }

   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: _pluginCreatePlayRewind
|  Begin: 01.11.2010
|  Author: Jo2003
|  Description: create play rewind folder ...
|
|  Parameters: channel id, gmt timestamp, video flag
|
|  Returns: array of media items
\----------------------------------------------------------------- */
function _pluginCreatePlayRewind($cid, $gmt, $isVideo)
{
   $retMediaItems = array();
   
   // forward / rewind values ...
   $offSetSecs    = "-600, -300, -120, -60, 0, 60, 120, 300, 600";
   
   // create array from values ...
   $offSetArr     = explode(", ", $offSetSecs);
   
   // master play item array ...
   $play_data = array(
      'cid'      => $cid,     // channel id
      'gmt'      => $gmt,     // timestamp for archive
      'is_video' => $isVideo, // video flag
      'dorec'    => false,    // record flag
      'offset'   => 0         // jump offset
   );
   
   // fill list with all array entries ...
   for ($i = 0; $i < count($offSetArr); $i++)
   {
      // update offset time ...
      $play_data['offset'] = (integer)$offSetArr[$i];
      
      // get offset in minutes ...
      $offInMins           = (integer)$offSetArr[$i] / 60;
      
      // build http query ...
      $play_data_query     = http_build_query($play_data);
      
      // rewind / forward or simple play ...
      if ($offInMins < 0)
      {
         $title = "- " .abs($offInMins) ." мин.";
         $icon  = LOC_KARTINA_URL."/images/clock.png";
      }
      else if ($offInMins > 0)
      {
         $title = "+ " . $offInMins . " мин.";
         $icon  = LOC_KARTINA_URL."/images/clock.png";
      }
      else
      {
         $title = "Старт Просмотр";
         $icon  = LOC_KARTINA_URL."/images/play.png";
      }
      
      simpleLog(__FUNCTION__."():".__LINE__." Add \"".$offInMins." min.\" Play Item (cid=".$cid
            .", is_video=".$isVideo.", dorec=false)");
      
      // add play item ...
      $retMediaItems[] = array (
         'id'             => LOC_KARTINA_UMSP."/kartina?".urlencode(md5($play_data_query)),
         'dc:title'       => $title,
         'upnp:class'     => ($isVideo) ? "object.item.videoitem" : "object.item.audioitem",
         'res'            => LOC_KARTINA_URL."/http-stream-recorder.php?".$play_data_query,
         'protocolInfo'   => "http-get:*:*:*",
         'upnp:album_art' => $icon
      );
   }

   return $retMediaItems;
}

/* -----------------------------------------------------------------\
|  Method: printItems
|  Begin: 8/13/2010 / 1:24p
|  Author: Jo2003
|  Description: debug function to print all available items ... 
|
|  Parameters: --
|
|  Returns: --
\----------------------------------------------------------------- */
function printItems()
{
   $items = _pluginCreateChannelGroupList();
   echo "<h3>Groups</h3>\n";

   for ($i = 0; $i < count($items); $i++)
   {
      echo ($i + 1)."<br />\n";
      echo $items[$i]['dc:title'] . " (".$items[$i]['id'].")<br />\n";
      echo "<img src='".$items[$i]['upnp:album_art']."' alt='' /><br />\n";
      echo "<hr /><br />\n";
   }
   
   $items = _pluginCreateFavList();
   echo "<h3>Favorites</h3>\n";

   for ($i = 0; $i < count($items); $i++)
   {
      echo ($i + 1)."<br />\n";
      echo $items[$i]['dc:title'] . " (".$items[$i]['id'].")<br />\n";
      echo "<img src='".$items[$i]['upnp:album_art']."' alt='' /><br />\n";
      echo "<hr /><br />\n";
   }
   
   $items = _pluginCreateChannelList(1);
   echo "<h3>Channels in Group 1</h3>\n";

   for ($i = 0; $i < count($items); $i++)
   {
      echo ($i + 1)."<br />\n";
      echo $items[$i]['dc:title'] . " (".$items[$i]['id'].")<br />\n";
      echo "<img src='".$items[$i]['upnp:album_art']."' alt='' /><br />\n";
      echo "<hr /><br />\n";
   }
}

/* -----------------------------------------------------------------\
|  Method: inArchive
|  Begin: 10/5/2010 / 3:46p
|  Author: Jo2003
|  Description: check if show is in archive time
|
|  Parameters: timestamp to check, time now
|
|  Returns: 0 ==> ok
|          -1 ==> any error
\----------------------------------------------------------------- */
function inArchive ($gmt, $now)
{
   if (($gmt >= ($now - MAX_ARCH_DAYS * DAY_IN_SECONDS)) // 14 days ...
      && ($gmt <= ($now - 600)))                         // 10 minutes ...
   {
      return TRUE;
   }
   else
   {
      return FALSE;
   }
}

/* -----------------------------------------------------------------\
|  Method: simpleLog
|  Begin: 10/07/2010 / 14:10
|  Author: Jo2003
|  Description: write log to log file
|
|  Parameters: log entry
|
|  Returns: --
\----------------------------------------------------------------- */
function simpleLog($str)
{
   if (DOTRACE == "YES")
   {
      $f = fopen("/tmp/kart_umsp.log", "a+");
      if ($f)
      {
         fwrite($f, date("d.m.y H:i:s").": ".$str."\n");
         fclose($f);
      }
   }
}
//////////////////////////////////////////////////////////////////
// for debug only                                               //
//////////////////////////////////////////////////////////////////
if (isset($_GET['trace']))
{
   if ($_GET['trace'] === "yes")
   {
      printItems();
   }
}

?>
