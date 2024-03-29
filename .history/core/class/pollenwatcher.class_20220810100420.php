<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* Includes */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class pollenwatcher extends eqLogic {
	
    /* Attributs */
	
	public static function getPollens(){
	
      return array(
			"Tilleul",
			"Ambroisies",
			"Olivier",
			"Plantain",
			"Noisetier",
			"Aulne",
			"Armoises",
			"Châtaignier",
			"Urticacées",
			"Oseille",
			"Graminées",
			"Chêne",
			"Platane",
			"Bouleau",
			"Charme",
			"Peuplier",
			"Frêne",
			"Saule",
			"Cyprès"
		);
	}
	
	public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);
	
    /* Methode static* */
     //Fonction exécutée automatiquement tous les jours par Jeedom cronDaily
  
	public static function cronDaily() {	
	  
		$eqLogics = self::byType('pollenwatcher', true);

		foreach ($eqLogics as $pollenwatcher) {
			try {
				if($pollenwatcher == null || $pollenwatcher->getIsEnable() == 0)
					continue;
			
				$pollenwatcher->updateData();
			} catch (Exception $e) {
				log::add('pollenwatcher', 'error', $e->getMessage());
			}
		}
    }
     
    /* Méthodes d'instance */

    public function postInsert() {	

		// pollenwatcher Info Creation
		foreach ($this->getPollens() as $key){
			$this->createPollenInfo($key, $key);
		}
		
		// Max Value info		
		$this->createPollenInfo("max_value", "Valeur Maximale", True);
		
		// Refresh command		
		$command = pollenwatcherCmd::byEqLogicIdAndLogicalId($this->getId(), "refresh");
		if(!is_object($command)) $command = new pollenwatcherCmd();
		$command->setName("Rafraichir");
		$command->setLogicalId("refresh");
		$command->setEqLogic_id($this->getId());
		$command->setType("action");
		$command->setSubType("other");
		$command->save();
    }

	
    public function preUpdate() {
		if ($this->getConfiguration('region_id') == '') {
			throw new Exception(__('Veuillez selectionner une région', __FILE__));
		}
    }

    public function postSave() {	
		
		if( $this->getIsEnable() == 0 )
			return;
		
		// Get Max Value command
		$cmd = $this->getCmd(null, 'max_value');
		$value = is_object($cmd) ? $cmd->execCmd() : 0;
		
		// Only at first save (max_value not set yet)
		if( strlen($value) <= 0 ) 
			$this->updateData();
    }
	
	private function createPollenInfo($logicalId, $name, $visibility = True) {	
			
		log::add('pollenwatcher', 'info', 'createPollenInfo: ' . $logicalId);	
		
		$info = pollenwatcherCmd::byEqLogicIdAndLogicalId($this->getId(), $logicalId);				
		if(is_object($info))
			return;		
		$info = new pollenwatcherCmd();		
		$info->setName($name);
		$info->setLogicalId($logicalId);
		$info->setEqLogic_id($this->getId());
		$info->setType("info");
		$info->setSubType("numeric");
		$info->setConfiguration('minValue', 0);
		$info->setConfiguration('maxValue', 5);
		if( $visibility == False )
			$info->setIsVisible(False);			
		$info->save();	
	}	
	
	public function updateData()
	{
		# Use the Curl extension to get details
      
		$url = 'https://pollens.fr/risks/thea/counties/' . sprintf("%02d",$this->getConfiguration("region_id"));
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$html = curl_exec($ch);
		curl_close($ch);

        # Decodage du format json
        $array = json_decode($html, true);
        $index = 0;
        $changed = false;
        foreach($array['risks'] as $element)
        {
          $value = $element['level'] ? $element['level'] : 0;
          $changed = $this->checkAndUpdateCmd($this->getPollens()[$index], $value ) || $changed;
          $index += 1;
        }
      
		$changed = $this->updateMaxValue() || $changed;			
		log::add('pollenwatcher', 'info', "Data updated for Region: " . $this->getConfiguration("region_id"));
		
		if ($changed)
			$this->refreshWidget();	
	}
	
	// Update the Max Value Command
	
	public function updateMaxValue()
	{		
		log::add('pollenwatcher', 'info', "updateMaxValue");
		$maxValue = 0;
		foreach ($this->getPollens() as $key)
		{
			$allergyCmd = $this->getCmd(null,  $key);
			$value = $allergyCmd->execCmd();
			if(($allergyCmd->getIsVisible() == 1 ) && ( $value > $maxValue ))
				$maxValue = $value;
		}
		log::add('pollenwatcher', 'info', "updateMaxValue: " . $maxValue);
		return $this->checkAndUpdateCmd('max_value', $maxValue );
	}

    public function toHtml($_version = 'dashboard') {
      
      $replace = $this->preToHtml($_version);
        
      if (!is_array($replace))  {	
          return $replace;
      }
      $version = jeedom::versionAlias($_version);

	  // Get global style template
      
      $globalStyle = $this->getConfiguration("global_style");
        if( $globalStyle == null) $globalStyle = 'global_style_circle_thin';
		$globalTemplate = '';
		if( $globalTemplate != 'none' ) $globalTemplate = getTemplate('core', $version, $globalStyle, 'pollenwatcher');
		$replace["#global_style#"] = $globalTemplate;
		
		//  Prepare allergy list
	
		$ordererArray;
		$maxLevel = 0;
		foreach ($this->getPollens() as $key){ // parcours du array getPollens()
          $allergyCmd = $this->getCmd(null,  $key); // getCmd($_type = null, $_logicalId = null, $_visible = null, $_multiple = false)
            if( $allergyCmd->getIsVisible() == 0 ) continue;
			$level = is_object($allergyCmd) ? $allergyCmd->execCmd() : 0;
			if( $level > $maxLevel ) $maxLevel = $level;
            $ordererArray[$level][] = $allergyCmd->getName();
        }
		
		$data = '';	
		for ($i=5; $i>0; $i--) {
          if(!array_key_exists($i, $ordererArray) ) continue;
          foreach($ordererArray[$i] as $key) {
            if(strlen($data)>0) $data .=  "<br/>";
            $data .= "<span><i class='fa fa-circle' style='font-size : 1em;color:". $this->getAllergyColor($i) . "'></i>&nbsp;&nbsp;" . $key . "</span>";
          }
		}		
		$replace["#data#"] = $data;
		
		//  Prepare global level (update CMD if needed)
		
		$status = $this->getCmd(null, 'max_value');
		if (is_object($status) && ($status->getIsVisible() == 1))
		{
          if($maxLevel != $status->execCmd()) {
            $status->setValue($maxLevel);
            $status->save();
          }
          $replace["#global_color#"]	= $this->getAllergyColor($maxLevel);
          $replace["#global_level#"]	= $status->execCmd();
		}
		else
		{
          $replace["#global_color#"]	= '';
          $replace["#global_level#"]	= '';
          $replace["#global_style#"]	= '';
		}
      $refresh = $this->getCmd(null, 'refresh');
      $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
      return template_replace($replace, getTemplate('core', $version, 'main', 'pollenwatcher'));
    }
  
	private function getAllergyColor($level)
	{
      if( $level == 1 ) return "#C1E9C1";
      else if ($level == 2 ) return "#00B050";
      else if ($level == 3 ) return "#FFFF00";
      else if ($level == 4 ) return "#FFA329";
      else if ($level == 5 ) return "#DF2B2F";
	  return "#FFFFFF";
	}    	 
}

/* pollenwatcherCmd */

class pollenwatcherCmd extends cmd {
  
  public static $_widgetPossibility = array('custom' => false);
  
  public function execute($_options = array()) {
    if ($this->getLogicalId() == 'refresh') {
      $this->getEqLogic()->updateData();
    }
    return false;
  }
}