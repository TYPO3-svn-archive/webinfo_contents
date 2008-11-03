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
		$theOutput .= $this->pObj->doc->spacer(10);

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
			// Get the page tree
		$tree = $this->getPageTree();

			// Parse the tree to get all the page id's
		$uidList = array();
		foreach ($tree->tree as $data) {
			if (!empty($data['row']['uid'])) {
				$uidList[] = $data['row']['uid'];
			}
		}
//t3lib_div::debug($uidList);

			// Get all the non-deleted content elements in those pages
			// This query does not take into account workspaces nor language overlays
		if (count($uidList) > 0) {
			$where = 'pid IN ('.implode(',', $uidList).')';
		}
		$deleteClause = t3lib_BEfunc::deleteClause('tt_content');
		if (!empty($deleteClause)) {
			if (empty($where)) {
				$where = '1 = 1';
			}
			$where .= $deleteClause;
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid) AS total, pid, CType, list_type', 'tt_content', $where, 'pid, CType, list_type', 'pid, CType, list_type');
			// Assemble a list of all content and plug-in types per page
		$contentElements = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (!isset($contentElements[$row['pid']])) $contentElements[$row['pid']] = array('standard' => array(), 'plugin' => array());
			if ($row['CType'] == 'list') {
				$type = 'plugin';
				$subtype = $row['list_type'];
			}
			else {
				$type = 'standard';
				$subtype = $row['CType'];
			}
			$contentElements[$row['pid']][$type][] = array('subtype' => $subtype, 'count' => $row['total']);
		}
//t3lib_div::debug($contentElements);

			// Get a list of all content element types
			// This is taken from the TCA of tt_content, minus the dummy first element
		$allContentElements = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
		array_shift($allContentElements);

			// Get a list of all plug-in types
			// This is taken from the TCA of tt_content, minus the dummy first element
		$allPlugins = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
		array_shift($allPlugins);

			// Initialise the table layout
		$tableLayout = array (
			'table' => array ('<table border="0" cellspacing="1" cellpadding="2" style="width:auto;">', '</table>'),
			'0' => array (
				'tr' => array('<tr class="bgColor2" valign="top">', '</tr>'),
			),
			'defRowOdd' => array (
				'tr' => array('<tr class="bgColor-10" valign="top">', '</tr>'),
				'1' => array('<td class="bgColor4-20">', '</td>'),
				'defCol' => array('<td>', '</td>'),
			),
			'defRowEven' => array (
				'tr' => array('<tr class="bgColor-20" valign="top">', '</tr>'),
				'1' => array('<td class="bgColor4-20">', '</td>'),
				'defCol' => array('<td>', '</td>'),
			)
		);
		$tableRows = array();
		$rowCounter = 0;
			// Define the table header row
		$tableRows[$rowCounter] = array('Pages');
		foreach ($allContentElements as $item) {
			$tableRows[$rowCounter][] = $GLOBALS['LANG']->sL($item[0]);
		}
		foreach ($allPlugins as $item) {
			$tableRows[$rowCounter][] = $GLOBALS['LANG']->sL($item[0]);
		}
		$rowCounter++;
			// Render the page tree with the content elements for each page
		foreach ($tree->tree as $row) {
			$page = $row['HTML'] . t3lib_BEfunc::getRecordTitle('pages', $row['row'], TRUE);
			$tableRows[$rowCounter] = array();
			$tableRows[$rowCounter][] = $page;
			$rowCounter++;
		}
		$table = $this->pObj->doc->table($tableRows, $tableLayout);
		return $table;
	}

	/**
	 * This method gets the page tree to display depening current page and chosen depth
	 *
	 * @return	object	Instance of t3lib_pageTree
	 */
	protected function getPageTree() {
		$treeStartingPoint = intval($this->pObj->id);
		$treeStartingRecord = t3lib_BEfunc::getRecord('pages', $treeStartingPoint);
		t3lib_BEfunc::workspaceOL('pages', $treeStartingRecord);
		$depth = isset($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_depth']) ? $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_depth'] : 2;

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
		return $tree;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webinfo_contents/modfunc1/class.tx_webinfocontents_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webinfo_contents/modfunc1/class.tx_webinfocontents_modfunc1.php']);
}

?>