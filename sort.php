<?php
/*
 * ** Zabbix
 * ** Copyright (C) 2001-2014 Zabbix SIA
 * **
 * ** This program is free software; you can redistribute it and/or modify
 * ** it under the terms of the GNU General Public License as published by
 * ** the Free Software Foundation; either version 2 of the License, or
 * ** (at your option) any later version.
 * **
 * ** This program is distributed in the hope that it will be useful,
 * ** but WITHOUT ANY WARRANTY; without even the implied warranty of
 * ** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * ** GNU General Public License for more details.
 * **
 * ** You should have received a copy of the GNU General Public License
 * ** along with this program; if not, write to the Free Software
 * ** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * **/

require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/hosts.inc.php';
require_once dirname(__FILE__).'/include/items.inc.php';

$page['title'] = _('Sort hosts by itemvalues');
$page['file'] = 'sort.php';
$page['hist_arg'] = array('group', 'item');
$page['scripts'] = array('class.cswitcher.js');
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

if ($page['type'] == PAGE_TYPE_HTML) {
        define('ZBX_PAGE_DO_REFRESH', 1);
}

validate_sort_and_sortorder('lastvalue', ZBX_SORT_DOWN);
$sortfield = getPageSortField('lastvalue');
$sortorder = getPageSortOrder();

require_once dirname(__FILE__).'/include/page_header.php';

$fields = array(
        'group' => array(T_ZBX_INT, O_OPT, P_SYS,  DB_ID,          null),
        'item'  => array(T_ZBX_STR, O_OPT, P_SYS,  null,           null)
);
check_fields($fields);

$_REQUEST['group'] = get_request('group');
$_REQUEST['item'] = get_request('item');

if (get_request('group') && !API::HostGroup()->isReadable(array($_REQUEST['group']))) {
        access_deny();
}

$sortWidget = new CWidget();

$groups = API::HostGroup()->get(array(
        'output' => API_OUTPUT_EXTEND
));
order_result($groups, 'name');

$group_combo = new CComboBox('group', $_REQUEST['group'], 'javascript: submit();');
foreach ($groups as $group) {
        $group_combo->addItem($group['groupid'], $group['name']);
}

$item_combo = new CComboBox('item', $_REQUEST['item'], 'javascript: submit();');
if (isset($_REQUEST['group'])) {
    $items = get_items_by_groupid($_REQUEST['group']);
    foreach ($items as $item) {
        $item_combo->addItem($item, $item);
    }
}

$rightForm = new CForm('get');
$rightForm->addItem(array(_('Group').SPACE, $group_combo));
$rightForm->addItem(array(SPACE._('Item').SPACE, $item_combo));

$sortWidget->addPageHeader(
        _('SORT HOSTVALUE').SPACE.'['.zbx_date2str(_('d M Y H:i:s')).']'
);
$sortWidget->addHeader(_('Sort hostvalue'), $rightForm);

$sortTable = new CTableInfo(_('No hostitem found.'));
$sortTable->setHeader(array(
        _('Host'),
        _('Last Time'),
        make_sorting_header(_('Last Value'), 'lastvalue'),
        make_sorting_header(_('Prev Value'), 'prevvalue'),
        _('Graph')
));

if (isset($_REQUEST['group']) && isset($_REQUEST['item'])) {
        $items = API::Item()->get(array(
                'output' => array('hostid', 'lastclock', 'lastvalue', 'prevvalue', 'itemid'),
                'groupids' => $_REQUEST['group'],
                'filter' => array(
                        'key_' => $_REQUEST['item']
                )
        ));
        order_result($items, $sortfield, $sortorder);

        foreach ($items as $item) {
                $host = get_host_by_hostid($item['hostid']);
                $sortTable->addRow(array(
                        $host['name'],
                        date('Y-m-d H:i:s', $item['lastclock']),
                        $item['lastvalue'],
                        $item['prevvalue'],
			new CLink(_('Show'), 'history.php?action=showgraph&itemid='.$item['itemid'])
                ));
        }
}

$sortWidget->addItem($sortTable);
$sortWidget->show();

require_once dirname(__FILE__).'/include/page_footer.php';

