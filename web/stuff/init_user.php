<?php
/**
 * File: init_user.php.
 * Author: Ulrich Block
 * Date: 30.01.13
 * Time: 11:14
 * Contact: <ulrich.block@easy-wi.com>
 *
 * This file is part of Easy-WI.
 *
 * Easy-WI is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Easy-WI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy-WI.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Diese Datei ist Teil von Easy-WI.
 *
 * Easy-WI ist Freie Software: Sie koennen es unter den Bedingungen
 * der GNU General Public License, wie von der Free Software Foundation,
 * Version 3 der Lizenz oder (nach Ihrer Wahl) jeder spaeteren
 * veroeffentlichten Version, weiterverbreiten und/oder modifizieren.
 *
 * Easy-WI wird in der Hoffnung, dass es nuetzlich sein wird, aber
 * OHNE JEDE GEWAEHELEISTUNG, bereitgestellt; sogar ohne die implizite
 * Gewaehrleistung der MARKTFAEHIGKEIT oder EIGNUNG FUER EINEN BESTIMMTEN ZWECK.
 * Siehe die GNU General Public License fuer weitere Details.
 *
 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
 * Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.
 */

if(!isset($user_id)) {
    header('Location: login.php');
    die;
}
$pa=User_Permissions($user_id);

# https://github.com/easy-wi/developer/issues/2
if (isset($_SESSION['sID'])) {
    $substituteAccess=array('gs'=>array(),'db'=>array(),'vo'=>array(),'vd'=>array(),'vs'=>array(),'ro'=>array());
    $query=$sql->prepare("SELECT `oID`,`oType` FROM `userdata_substitutes_servers` WHERE `sID`=?");
    $query->execute(array($_SESSION['sID']));
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) $substituteAccess[$row['oType']][]=$row['oID'];
    $query=$sql->prepare("SELECT `name`,`vname`,`lastlogin` FROM `userdata_substitutes` WHERE `sID`=? LIMIT 1");
    $query->execute(array($_SESSION['sID']));
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $great_name=$row['name'];
        $great_vname=$row['vname'];
        $lastlogin=$row['lastlogin'];
    }
    $gscount=count($substituteAccess['gs']);
    $voicecount=count($substituteAccess['vo']);
    $tsdnscount=count($substituteAccess['vd']);
    $dbcount=count($substituteAccess['db']);
    $rootcount=count($substituteAccess['ro']);
    $virtualcount=count($substituteAccess['vs']);
} else {
    $query=$sql->prepare("SELECT `name`,`vname`,`lastlogin` FROM `userdata` WHERE `id`=? LIMIT 1");
    $query->execute(array($user_id));
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $great_name=$row['name'];
        $great_vname=$row['vname'];
        $lastlogin=$row['lastlogin'];
    }
    $query=$sql->prepare("SELECT COUNT(g.`id`) AS `amount` FROM `gsswitch` g INNER JOIN `rserverdata` r ON g.`rootID`=r.`id` WHERE r.`active`='Y' AND g.`active`='Y' AND g.`userid`=? LIMIT 1");
    $query->execute(array($user_id));
    $gscount=$query->fetchColumn();
    $query=$sql->prepare("SELECT COUNT(v.`id`) AS `amount` FROM `voice_server` v INNER JOIN `voice_masterserver` m ON v.`masterserver`=m.`id` WHERE v.`active`='Y' AND m.`active`='Y' AND v.`userid`=? LIMIT 1");
    $query->execute(array($user_id));
    $voicecount=$query->fetchColumn();
    $query=$sql->prepare("SELECT COUNT(e.`id`) AS `amount` FROM `mysql_external_dbs` e INNER JOIN `mysql_external_servers` s ON e.`sid`=s.`id` WHERE e.`active`='Y' AND s.`active`='Y' AND e.`uid`=? LIMIT 1");
    $query->execute(array($user_id));
    $dbcount=$query->fetchColumn();
    $query=$sql->prepare("SELECT COUNT(d.`dnsID`) AS `amount` FROM `voice_dns` d INNER JOIN `voice_tsdns` s ON d.`tsdnsID`=s.`id` WHERE d.`active`='Y' AND s.`active`='Y' AND d.`userID`=? LIMIT 1");
    $query->execute(array($user_id));
    $tsdnscount=$query->fetchColumn();
    $query=$sql->prepare("SELECT COUNT(`dedicatedID`) AS `amount` FROM `rootsDedicated` WHERE `active`='Y' AND `userID`=? LIMIT 1");
    $query->execute(array($user_id));
    $rootcount=$query->fetchColumn();
    $query=$sql->prepare("SELECT COUNT(`id`) AS `amount` FROM `virtualcontainer` WHERE `active`='Y' AND `userid`=? LIMIT 1");
    $query->execute(array($user_id));
    $virtualcount=$query->fetchColumn();
}
if ($lastlogin!=null and $lastlogin!='0000-00-00 00:00:00' and $user_language=='de') $great_last=date('d.m.Y H:m:s',strtotime($lastlogin));
else if ($lastlogin!=null and $lastlogin!='0000-00-00 00:00:00') $great_last=$lastlogin;
else if ($user_language=='de') $great_last='Niemals';
else $great_last='Never';

# basic controler array. available at any time to anyone
$what_to_be_included_array=array('ip'=>'imprint.php','lo'=>'userpanel_logdata.php','ti'=>'userpanel_tickets.php');

# controlers meant only for user only
if (!isset($_SESSION['sID'])) {
    $what_to_be_included_array['su']='userpanel_substitutes.php';
    $what_to_be_included_array['se']='global_userdata.php';
}

# controler based on count. No servers, no controler
if ($gscount>0) {
    $what_to_be_included_array['gs']='userpanel_gserver.php';
    $what_to_be_included_array['fd']='userpanel_fdl.php';
    $what_to_be_included_array['ao']='userpanel_ao.php';
    $what_to_be_included_array['ca']='userpanel_restartcalendar.php';
    $what_to_be_included_array['pr']='userpanel_protectionmode.php';
    $what_to_be_included_array['bu']='userpanel_backup.php';
    $what_to_be_included_array['ms']='userpanel_migration.php';
}
if ($voicecount>0) $what_to_be_included_array['vo']='userpanel_voice.php';
if ($tsdnscount>0) $what_to_be_included_array['vd']='userpanel_voice_dns.php';
if ($rootcount>0) $what_to_be_included_array['de']='userpanel_dedicated.php';
if ($virtualcount>0) $what_to_be_included_array['vm']='userpanel_virtual.php';
if ($dbcount>0) $what_to_be_included_array['my']='userpanel_mysql.php';