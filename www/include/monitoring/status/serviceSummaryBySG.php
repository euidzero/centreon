<?
/**
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/gpl.txt
Developped by : Julien Mathis - Romain Le Merlus

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/

	if (!isset($oreon))
		exit();

	unset($TabLca);;
	$TabLca = getLcaHostByName($pearDB);

	$h_data = array();
	$svc_data = array();
	$svc_status = array();
	$servicegroup = array();
	$tab_color = array(0=>"list_one", 1=>"list_two");

	$counter_host = 0;
	
	$DBRESULT =& $pearDB->query("SELECT sg_id, sg_name, sg_alias FROM servicegroup WHERE sg_activate = '1' ORDER BY sg_name");
	if (PEAR::isError($DBRESULT))
		print "Mysql Error : ".$DBRESULT->getMessage();
	while ($DBRESULT->fetchInto($r)){
		unset($svc_status);
		$servicegroup[$r["sg_name"]] = $r["sg_alias"];
		$DBRESULT_relation =& $pearDB->query("SELECT * FROM servicegroup_relation WHERE servicegroup_sg_id = '".$r["sg_id"]."'");
		if (PEAR::isError($DBRESULT))
			print "Mysql Error : ".$DBRESULT_relation->getMessage();
		while ($DBRESULT_relation->fetchInto($sg_relation)){
			if ($sg_relation["host_host_id"]){
				# Get Host_name
				$host_name = getMyHostName($sg_relation["host_host_id"]);
				if ($oreon->user->admin || !$isRestreint || ($isRestreint && isset($TabLca["LcaHost"][$host_name]))){
					# Get Servicce description
					$service_description = getMyServiceName($sg_relation["service_service_id"]);
					if ((isset($_GET["problem"]) && $service_status[$host_name."_".$service_description]["current_state"] != "OK") 
					|| 	(!isset($_GET["problem"]) && !isset($_GET["acknowledge"])) 
					|| 	(!isset($_GET["problem"]) && isset($_GET["acknowledge"]) && $_GET["acknowledge"] == 1 && $service_status[$host_name."_".$service_description]["problem_has_been_acknowledged"] == 1)
					|| 	(!isset($_GET["problem"]) && isset($_GET["acknowledge"]) && $_GET["acknowledge"] == 0 && $service_status[$host_name."_".$service_description]["problem_has_been_acknowledged"] == 0 && $service_status[$host_name."_".$service_description]["current_state"] != "OK" )){			
						if (isset($service_status[$host_name."_".$service_description])){	
							if (!isset($h_data[$r["sg_name"]]))
								$h_data[$r["sg_name"]] = array();
							$h_data[$r["sg_name"]][$host_name] = "<a href='./oreon.php?p=201&o=hd&host_name=".$host_name."'>" . $host_name . "</a> ";
							
							$h_status[$host_name] = array("current_state"=>$host_status[$host_name]["current_state"], "color"=>$oreon->optGen["color_".strtolower($host_status[$host_name]["current_state"])]);
							if (!isset($svc_data[$r["sg_name"]]))
								$svc_data[$r["sg_name"]] = array();
							if (!isset($svc_status[$host_name]))
								$svc_status[$host_name] = array();
							
							if (!isset($svc_status[$host_name][$service_status[$host_name."_".$service_description]["current_state"]]))
								$svc_status[$host_name][$service_status[$host_name."_".$service_description]["current_state"]] = 1;
							else
								$svc_status[$host_name][$service_status[$host_name."_".$service_description]["current_state"]]++;
								
							if (!isset($svc_data[$r["sg_name"]][$host_name]))
								$svc_data[$r["sg_name"]][$host_name] = "";
							
							# define class
							isset($host_status[$host_name]) && $host_status[$host_name]["current_state"] == "DOWN" ? $h_class[$r["sg_name"]][$host_name] = "list_down" : $h_class[$r["sg_name"]][$host_name] = $tab_color[++$counter_host % 2];
						}
					}
				}
			}
		}
		if (isset($svc_status))
			foreach ($svc_status as $key => $value){
				$svc_data[$r["sg_name"]][$key] = "";
				foreach ($value as $key_stt => $value_stt){
					$svc_data[$r["sg_name"]][$key] .= "<span style='background:".$oreon->optGen["color_".strtolower($key_stt)]."'>$value_stt <a href='./oreon.php?p=202&o=svcd&host_name=".""."&service_description=".""."'>".$key_stt."</a></span>&nbsp; \n";
				}
			}
	}
	
	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl, "/templates/");
	$tpl->assign("refresh", $oreon->optGen["oreon_refresh"]);
	$tpl->assign("p", $p);
	$tpl->assign("lang", $lang);
	$tpl->assign("servicegroup", $servicegroup);
	if (isset($h_data))
		$tpl->assign("h_data", $h_data);
	if (isset($h_status))
		$tpl->assign("h_status", $h_status);
	if (isset($h_class))
		$tpl->assign("h_class", $h_class);
	$tpl->assign("svc_data", $svc_data);
	$tpl->display("serviceOverviewBySG.ihtml");
?>