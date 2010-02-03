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
***************************************************************/

require_once(PATH_t3lib . 'class.t3lib_extobjbase.php');

/**
 * Module extension (addition to function menu) 'Content elements overview' for the 'webinfo_contents' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_webinfocontents
 *
 * $Id$
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
				'overview' => $GLOBALS['LANG']->getLL('overview'),
				'search' => $GLOBALS['LANG']->getLL('search')
			),
			'tx_webinfocontents_modfunc1_depth' => array(
				0	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_0'),
				1	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_1'),
				2	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_2'),
				3	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_3'),
				250	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
			),
			'tx_webinfocontents_modfunc1_search' => 0
		);
		return $menu;
	}

	/**
	 * This is the main method of the module
	 * It assemble the base content and dispatches the rest to the relevant methods
	 *
	 * @return	string	HTML to display
	 */
	public function main() {
//t3lib_div::debug($this->pObj);
			// If no page is selected, display general information message
		if (empty($this->pObj->id)) {
			$sectionContent = $GLOBALS['LANG']->getLL('choose_page');
			$theOutput .= $this->pObj->doc->spacer(5);
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $sectionContent, 0, 1);
		}
		else {
			// Assemble the menu of options
			$sectionContent = $GLOBALS['LANG']->getLL('choose_view') . ' ' . t3lib_BEfunc::getFuncMenu($this->pObj->id, 'SET[tx_webinfocontents_modfunc1_display]', $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_display'], $this->pObj->MOD_MENU['tx_webinfocontents_modfunc1_display']);
			$sectionContent .= $GLOBALS['LANG']->getLL('depth') . ' ' . t3lib_BEfunc::getFuncMenu($this->pObj->id, 'SET[tx_webinfocontents_modfunc1_depth]', $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_depth'], $this->pObj->MOD_MENU['tx_webinfocontents_modfunc1_depth']);
			$theOutput .= $this->pObj->doc->spacer(5);
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $sectionContent, 0, 1);
			$theOutput .= $this->pObj->doc->spacer(10);

			// Dispatch display to relevant method
			if ($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_display'] == 'search') {
				$theOutput .= $this->displaySearch();
			}
			else {
				$theOutput .= $this->displayGlobalOverview();
			}
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
		$uidList = $this->getPageIdsFromTree($tree);

			// Get all the non-deleted content elements in those pages
			// This query does not take into account workspaces nor language overlays
		if (count($uidList) > 0) {
			$where = 'pid IN (' . implode(',', $uidList) . ')';
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
			$contentElements[$row['pid']][$type][$subtype] = $row['total'];
		}
//t3lib_div::debug($contentElements);

			// Get a list of all content element types
		$allContentElements = $this->getAllContentElementTypes();

			// Get a list of all plug-in types
		$allPlugins = $this->getAllPluginTypes();

			// Initialise the table layout
		$tableLayout = array (
			'table' => array ('<table border="0" cellspacing="1" cellpadding="2" style="width:auto;">', '</table>'),
			'0' => array (
				'tr' => array('<tr class="bgColor2" valign="top">', '</tr>'),
				'0' => array('<td>', '</td>'),
				'1' => array('<td colspan="' . count($allContentElements) . '" align="center">', '</td>'),
				'2' => array('<td colspan="' . count($allPlugins) . '" align="center">', '</td>'),
			),
			'1' => array (
				'tr' => array('<tr class="bgColor2" valign="top">', '</tr>'),
			),
			'defRowOdd' => array (
				'tr' => array('<tr class="bgColor-10" valign="top">', '</tr>'),
				'0' => array('<td nowrap="nowrap">', '</td>'),
				'defCol' => array('<td>', '</td>'),
			),
			'defRowEven' => array (
				'tr' => array('<tr class="bgColor-20" valign="top">', '</tr>'),
				'0' => array('<td nowrap="nowrap">', '</td>'),
				'defCol' => array('<td>', '</td>'),
			)
		);
		$tableRows = array();
		$rowCounter = 0;
			// Define the first table header row
		$tableRows[$rowCounter] = array($GLOBALS['LANG']->getLL('pages'), $GLOBALS['LANG']->getLL('content_elements'), $GLOBALS['LANG']->getLL('plugins'));
		$rowCounter++;
			// Define the second table header row
		$tableRows[$rowCounter] = array('&nbsp;');
		foreach ($allContentElements as $item) {
			$tableRows[$rowCounter][] = $GLOBALS['LANG']->sL($item[0]);
		}
		foreach ($allPlugins as $item) {
			$tableRows[$rowCounter][] = $GLOBALS['LANG']->sL($item[0]);
		}
		$rowCounter++;

			// Render the page tree with the content elements for each page
		foreach ($tree->tree as $row) {
			$pid = $row['row']['uid'];
			$page = $row['HTML'] . t3lib_BEfunc::getRecordTitle('pages', $row['row'], TRUE);
			$tableRows[$rowCounter] = array();
			$tableRows[$rowCounter][] = $page;
			foreach ($allContentElements as $item) {
				if (empty($contentElements[$pid]['standard'][$item[1]])) {
					$cell = '&nbsp;';
				}
				else {
					$cell = $contentElements[$pid]['standard'][$item[1]];
				}
				$tableRows[$rowCounter][] = $cell;
			}
			foreach ($allPlugins as $item) {
				if (empty($contentElements[$pid]['plugin'][$item[1]])) {
					$cell = '&nbsp;';
				}
				else {
					$cell = $contentElements[$pid]['plugin'][$item[1]];
				}
				$tableRows[$rowCounter][] = $cell;
			}
			$rowCounter++;
		}
		$output = $this->pObj->doc->table($tableRows, $tableLayout);
		return $output;
	}

	/**
	 * This method displays the search page
	 * 
	 * @return	string	HTML to display
	 */
	protected function displaySearch() {
		// Assemble content element or plugin selection menu
		$output = '<p>' . $GLOBALS['LANG']->getLL('choose_content_or_plugin') . '</p>';
		$output .= '<select name="SET[tx_webinfocontents_modfunc1_search]" onchange="jumpToUrl(\'index.php?id=' . $this->pObj->id . '&SET[tx_webinfocontents_modfunc1_search]=\'+this.options[this.selectedIndex].value,this);">' . "\n";
		$output .= '<optgroup label="' . $GLOBALS['LANG']->getLL('content_elements') . '">' . "\n";
		$allContentElements = $this->getAllContentElementTypes();
		foreach ($allContentElements as $item) {
			if (isset($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search']) && $item[1] == $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search']) {
				$selected = ' selected="selected"';
			}
			else {
				$selected = '';
			}
			$output .= '<option value="' . $item[1] . '"' . $selected . '>' . $GLOBALS['LANG']->sL($item[0]) . '</option>';
		}
		$output .= '</optgroup>' . "\n";
		$output .= '<optgroup label="' . $GLOBALS['LANG']->getLL('plugins') . '">' . "\n";
		$allPlugins = $this->getAllPluginTypes();
		foreach ($allPlugins as $item) {
			if (isset($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search']) && $item[1] == $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search']) {
				$selected = ' selected="selected"';
			}
			else {
				$selected = '';
			}
			$output .= '<option value="' . $item[1] . '"' . $selected . '>' . $GLOBALS['LANG']->sL($item[0]) . ' (' . $item[1] .')</option>';
		}
		$output .= '</optgroup>' . "\n";
		$output .= '</select>';

			// Search for the appropriate type if the search field is not empty
		if (!empty($this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search'])) {
				// Get the page tree
			$tree = $this->getPageTree();

				// Parse the tree to get all the page id's
			$uidList = $this->getPageIdsFromTree($tree);

				// Search for the selected content element or plugin type
			$where = "(CType = '" . $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search'] . "' OR list_type = '" . $this->pObj->MOD_SETTINGS['tx_webinfocontents_modfunc1_search'] . "')";

				// Restrict query to selected pages
			if (count($uidList) > 0) {
				$where .= ' AND pid IN (' . implode(',', $uidList) . ')';
			}
			$deleteClause = t3lib_BEfunc::deleteClause('tt_content');
			if (!empty($deleteClause)) {
				$where .= $deleteClause;
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT pid', 'tt_content', $where);
				// If the query returned no results, issue information
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
				$searchResults = '<p>' . $GLOBALS['LANG']->getLL('search_no_results') . '</p>';
			}
				// If some results were returned, display list of pages found
			else {
				$searchResults = '<p>' . $GLOBALS['LANG']->getLL('search_results_intro') . '</p>';
				$searchResults .= $this->pObj->doc->spacer(5);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$pageRecord = t3lib_BEfunc::getRecord('pages', $row['pid']);
					$pageDisplay = '<a href="#" onclick="' . htmlspecialchars('top.loadEditId(' . $row['pid'] . ')') . '">';
					$pageDisplay .= t3lib_iconWorks::getIconImage('pages', $pageRecord, $GLOBALS['BACK_PATH'], 'align="top"');
					$pageDisplay .= '[' . $row['pid'] . '] ' . t3lib_BEfunc::getRecordTitle('pages', $pageRecord, TRUE);;
					$pageDisplay .= '</a>';
					$searchResults .= '<p>' . $pageDisplay . '</p>';
				}
				$searchResults .= $this->pObj->doc->spacer(10);
			}
			$output .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('search_results'), $searchResults, 0, 1);
		}
		return $output;
	}

	/**
	 * This method gets the page tree to display depending current page and chosen depth
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
		$tree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));

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

	/**
	 * This method reads the TCA of the tt_content table to get the list of all content elements types
	 *
	 * @return	array	Array of all content element types as per TCA
	 */
	protected function getAllContentElementTypes() {
			// The base list is taken from the TCA of tt_content
		$allContentElements = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
			// Remove the dummy first element
		array_shift($allContentElements);
			// Filter out the "list_type" type as plug-ins are displayed separately
		foreach ($allContentElements as $index => $item) {
			if ($item[1] == 'list') {
				unset($allContentElements[$index]);
				break;
			}
		}
		return $allContentElements;
	}

	/**
	 * This method reads the TCA of the tt_content table to get the list of all plugin types
	 * 
	 * @return	array	Array of all plugin types as per TCA
	 */
	protected function getAllPluginTypes() {
		$allPlugins = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
		array_shift($allPlugins);
		return $allPlugins;
	}

	/**
	 * This method parses a tree object and returns all the page uid's found within it
	 *
	 * @param	object	$tree: an instance of the t3lib_pageTree class
	 * @return	array	A list of page uid's
	 */
	protected function getPageIdsFromTree($tree) {
		$uidList = array();
		foreach ($tree->tree as $data) {
			if (!empty($data['row']['uid'])) {
				$uidList[] = $data['row']['uid'];
			}
		}
		return $uidList;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webinfo_contents/modfunc1/class.tx_webinfocontents_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webinfo_contents/modfunc1/class.tx_webinfocontents_modfunc1.php']);
}

?>