<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id$
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_t3lib.'class.t3lib_extobjbase.php');

/**
 * Module extension (addition to function menu) 'Content elements overview' for the 'webinfo_contents' extension.
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_webinfocontents
 */
class tx_webinfocontents_modfunc1 extends t3lib_extobjbase {

	/**
	 * This method assembles the module's submenu and returns it
	 *
	 * @return	array with menuitems
	 */
	public function modMenu() {
		$menu = array(
			'tx_webinfocontents_modfunc1_display' => array(
				'overview' => 'Global overview',
				'search' => 'Search'
			),
			'tx_webinfocontents_modfunc1_depth' => array(
				0	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_0'),
				1	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_1'),
				2	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_2'),
				3	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_3'),
				250	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
			),
		);
		return $menu;
	}

	/**
	 * This is the main method of the module
	 * It assemble the base content and dispatches the rest to the relevant methods
	 *
	 * @return	string	HTML to display
	 */
	public function main()	{
		// Assemble the menu of options
		$sectionContent = t3lib_BEfunc::getFuncMenu($this->wizard->pObj->id, 'SET[tx_webinfocontents_modfunc1_display]', $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_display'], $this->pObj->MOD_MENU['tx_webinfocontents_modfunc1_display']);
		if (empty($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_display']) || $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_display'] == 'overview') {
			$sectionContent .= $GLOBALS['LANG']->getLL('depth').': '.t3lib_BEfunc::getFuncMenu($this->wizard->pObj->id, 'SET[tx_webinfocontents_modfunc1_depth]', $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_depth'], $this->pObj->MOD_MENU['tx_webinfocontents_modfunc1_depth']);
		}
		$theOutput .= $this->pObj->doc->spacer(5);
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $sectionContent, 0, 1);

		// Dispatch display to relevant method
		if ($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_display'] == 'search') {

		}
		else {
			$theOutput .= $this->displayGlobalOverview();
		}
		return $theOutput;
	}

	/**
	 * This method assembles the global overview display
	 *
	 * @return	string	HTML to display
	 */
	protected function displayGlobalOverview() {
		$treeStartingPoint = intval($this->pObj->id);
		$treeStartingRecord = t3lib_BEfunc::getRecord('pages', $treeStartingPoint);
		t3lib_BEfunc::workspaceOL('pages', $treeStartingRecord);
		$depth = isset($this->pObj->MOD_SETTINGS['depth']) ? $this->pObj->MOD_SETTINGS['depth'] : 2;

			// Initialize tree object:
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->addField('nav_title', 1);
		$tree->init('AND '.$GLOBALS['BE_USER']->getPagePermsClause(1));

			// Creating top icon; the current page
		$HTML = t3lib_iconWorks::getIconImage('pages', $treeStartingRecord, $GLOBALS['BACK_PATH'], 'align="top"');
		$tree->tree[] = array(
			'row' => $treeStartingRecord,
			'HTML' => $HTML
		);

			// Create the tree from starting point:
		if ($depth > 0) {
			$tree->getTree($treeStartingPoint, $depth, '');
		}
			// Parse the tree to get all the page id's
		$uidList = array();
		foreach ($tree->tree as $data) {
			$uidList[] = $data['row']['uid'];
		}
//t3lib_div::debug($uidList);

	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webinfo_contents/modfunc1/class.tx_webinfocontents_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webinfo_contents/modfunc1/class.tx_webinfocontents_modfunc1.php']);
}

?>