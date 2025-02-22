<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsField
 */
class PluginMetademandsField extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

    // Request type
    const MAX_FIELDS = 40;

    // Request type
    const CLASSIC_DISPLAY = 0;
    // Demand type
    const DOUBLE_COLUMN_DISPLAY = 1;

    public static $dropdown_meta_items     = ['', 'other', 'ITILCategory_Metademands', 'urgency', 'impact', 'priority',
                                              'mydevices'];
    public static $dropdown_multiple_items = ['other', 'Appliance', 'User'];

    public static $field_types = ['', 'dropdown', 'dropdown_object', 'dropdown_meta', 'dropdown_multiple', 'text',
                                  'checkbox', 'textarea', 'date', 'datetime', 'informations', 'date_interval',
                                  'datetime_interval', 'yesno','upload', 'title', 'title-block', 'radio', 'link',
                                  'number', 'parent_field'];

    public static $allowed_options_types = ['yesno', 'date', 'datetime', 'date_interval', 'datetime_interval',
                                            'checkbox', 'radio','dropdown_multiple', 'dropdown', 'dropdown_object',
                                            'parent_field', 'number', 'text','textarea', 'upload'];
    public static $allowed_options_items = ['other', 'ITILCategory_Metademands'];

    public static $allowed_custom_types = ['checkbox', 'yesno', 'radio', 'link', 'dropdown_multiple'];
    public static $allowed_custom_items = ['other'];

    public static $not_null = 'NOT_NULL';


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
    public static function getTypeName($nb = 0)
    {
        return __('Wizard creation', 'metademands');
    }

   /**
    * @return bool|int
    */
    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

   /**
    * @return bool
    */
    public static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

   /**
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            if ($item->getType() == 'PluginMetademandsMetademand') {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        self::getTypeName(),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["plugin_metademands_metademands_id" => $item->getID()]
                        )
                    );
                }
                return self::getTypeName();
            }
        }
        return '';
    }

   /**
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == 'PluginMetademandsMetademand') {
            $field->showForm(0, ["item" => $item]);
        }
        return true;
    }

   /**
    * @param array $options
    *
    * @return array
    * @see CommonGLPI::defineTabs()
    */
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginMetademandsFieldTranslation', $ong, $options);

        return $ong;
    }


   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
    public function showForm($ID, $options = [])
    {
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }
        Html::requireJs('tinymce');

        $metademand = new PluginMetademandsMetademand();

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            $item    = $options['item'];
            $canedit = $metademand->can($item->fields['id'], UPDATE);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
            $this->fields['color']                             = '#000';
        }

        // Data saved in session
        if (isset($_SESSION['glpi_plugin_metademands_fields'])) {
            foreach ($_SESSION['glpi_plugin_metademands_fields'] as $key => $value) {
                $this->fields[$key] = $value;
            }
            unset($_SESSION['glpi_plugin_metademands_fields']);
        }


        if ($ID > 0) {
            $this->showFormHeader(['colspan' => 2]);
        } else {
            echo "<div class='center first-bloc'>";
            echo "<form name='field_form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='6'>" . __('Add a field', 'metademands') . "</th>";
            echo "</tr>";
        }

        $metademand_fields = new self();
        $metademand_fields->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->fields['plugin_metademands_metademands_id'],
                                           'item'                              => 'ITILCategory_Metademands']);
        $categories = [];
        if (isset($metademand->fields['itilcategories_id'])) {
            if (is_array(json_decode($metademand->fields['itilcategories_id'], true))) {
                $categories = json_decode($metademand->fields['itilcategories_id'], true);
            }
        }

        if (count($metademand_fields->fields) < 1 && count($categories) > 1) {
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo "<i class='fas fa-exclamation-triangle fa-3x'></i>&nbsp;" . __('Please add a type category field', 'metademands');
            echo "</div>";
        }

        echo "<tr class='tab_bg_1'>";

        // LABEL
        echo "<td>" . __('Label') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
        echo "<td>";
        echo Html::input('name', ['value' => stripslashes($this->fields["name"]), 'size' => 40]);
        if ($ID > 0) {
            echo Html::hidden('entities_id', ['value' => $this->fields["entities_id"]]);
            echo Html::hidden('is_recursive', ['value' => $this->fields["is_recursive"]]);
        } else {
            echo Html::hidden('entities_id', ['value' => $item->fields["entities_id"]]);
            echo Html::hidden('is_recursive', ['value' => $item->fields["is_recursive"]]);
        }
        echo "</td>";

        // MANDATORY
        echo "<td>" . __('Mandatory field') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("is_mandatory", $this->fields["is_mandatory"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Hide title', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('hide_title', ($this->fields['hide_title']));
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

        // LABEL 2
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Additional label', 'metademands') ;
        echo "&nbsp;<span id='show_label2' style='color:red;display:none;'>&nbsp;*&nbsp;</span>";
        echo "</td>";
        echo "<td>";
        $label2 = Html::cleanPostForTextArea($this->fields['label2']);
        Html::textarea(['name'              => 'label2',
                      'value'             => $label2,
                      'enable_richtext'   => true,
                      'enable_fileupload' => false,
                      'enable_images'     => true,
                      'cols'              => 50,
                      'rows'              => 3]);
       //      Html::autocompletionTextField($this, "label2", ['value' => stripslashes($this->fields["label2"])]);
        echo "</td>";

        // COMMENT
        echo "<td>" . __('Comments') . "</td>";
        echo "<td>";
        $comment = Html::cleanPostForTextArea($this->fields['comment']);
        Html::textarea(['name'              => 'comment',
                      'value'             => $comment,
                      'enable_richtext'   => true,
                      'enable_fileupload' => false,
                      'enable_images'     => false,
                      'cols'              => 50,
                      'rows'              => 3]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Takes the whole row', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('row_display', ($this->fields['row_display']));
        echo "</td>";


        // BLOCK
        echo "<td>" . __('Block', 'metademands') . "</td>";
        echo "<td>";
        $randRank   = Dropdown::showNumber('rank', ['value' => $this->fields["rank"],
                                                  'min'   => 1,
                                                  'max'   => self::MAX_FIELDS]);
        $paramsRank = ['rank'               => '__VALUE__',
                     'step'               => 'order',
                     'fields_id'          => $this->fields['id'],
                     'metademands_id'     => $this->fields['plugin_metademands_metademands_id'],
                     'previous_fields_id' => $this->fields['plugin_metademands_fields_id']];
        Ajax::updateItemOnSelectEvent('dropdown_rank' . $randRank, "show_order", PLUGIN_METADEMANDS_WEBDIR .
                                                                               "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsRank);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        // TYPE
        echo "<td>" . __('Type') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
        echo "<td>";
        $randType   = self::dropdownFieldTypes("type", ['value'          => $this->fields["type"],
                                                      'metademands_id' => $this->fields["plugin_metademands_metademands_id"]]);
        $paramsType = ['value'                   => '__VALUE__',
                     'type'                    => '__VALUE__',
                     'item'                    => $this->fields['item'],
                     'task_link'               => $this->fields['plugin_metademands_tasks_id'],
                     'fields_link'             => $this->fields['fields_link'],
                     'max_upload'              => $this->fields['max_upload'],
                     'regex'                   => $this->fields['regex'],
                     'use_date_now'            => $this->fields['use_date_now'],
                     'additional_number_day'   => $this->fields['additional_number_day'],
                     'display_type'            => $this->fields['display_type'],
                     'informations_to_display' => $this->fields['informations_to_display'],
                     //                     'fields_display' => $this->fields['fields_display'],
                     'hidden_link'             => $this->fields['hidden_link'],
                     'hidden_block'            => $this->fields['hidden_block'],
                     'childs_blocks'           => $this->fields['childs_blocks'],
                     'users_id_validate'       => $this->fields['users_id_validate'],
                     'custom_values'           => $this->fields['custom_values'],
                     'comment_values'          => $this->fields['comment_values'],
                     'default_values'          => $this->fields['default_values'],
                     'check_value'             => $this->fields['check_value'],
                     'metademands_id'          => $this->fields["plugin_metademands_metademands_id"],
                     'link_to_user'            => $this->fields["link_to_user"],
                     'change_type'             => 1];
        Ajax::updateItemOnSelectEvent('dropdown_type' . $randType, "show_values", PLUGIN_METADEMANDS_WEBDIR .
                                                                                "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsType);


        echo "</td>";

        // ORDER
        if ($this->fields['type'] != "title-block") {
            echo "<td>" . __('Display field after', 'metademands') . "</td>";
            echo "<td>";
            echo "<span id='show_order'>";
            $this->showOrderDropdown(
                $this->fields['rank'],
                $this->fields['id'],
                $this->fields['plugin_metademands_fields_id'],
                $this->fields["plugin_metademands_metademands_id"]
            );
            echo "</span>";
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        // ITEM
        //Display for dropdown list
        echo "<td>";
        echo "<span id='show_item_object' style='display:none'>";
        echo __('Object', 'metademands') . "<span style='color:red'>&nbsp;*&nbsp;</span>";
        echo "</span>";

        //Display to add a title
        echo "<span id='show_item_label_title' style='display:none'>";
        echo __('Color') . "<span style='color:red'>&nbsp;*&nbsp;</span>";
        echo "</span>";
        echo "</td>";
        echo "<td>";
        echo "<span id='show_item' style='display:none'>";
        $randItem = self::dropdownFieldItems("item", $this->fields["type"], ['value' => $this->fields["item"]]);
        echo "</span>";
        $paramsType = ['value'                   => '__VALUE__',
                     'type'                    => '__VALUE__',
                     'item'                    => $this->fields['item'],
                     'task_link'               => $this->fields['plugin_metademands_tasks_id'],
                     'fields_link'             => $this->fields['fields_link'],
                     'max_upload'              => $this->fields['max_upload'],
                     'regex'                   => $this->fields['regex'],
                     'use_date_now'            => $this->fields['use_date_now'],
                     'additional_number_day'   => $this->fields['additional_number_day'],
                     'display_type'            => $this->fields['display_type'],
                     'informations_to_display' => $this->fields['informations_to_display'],
                     //                     'fields_display' => $this->fields['fields_display'],
                     'hidden_link'             => $this->fields['hidden_link'],
                     'hidden_block'            => $this->fields['hidden_block'],
                     'custom_values'           => $this->fields['custom_values'],
                     'comment_values'          => $this->fields['comment_values'],
                     'default_values'          => $this->fields['default_values'],
                     'check_value'             => $this->fields['check_value'],
                     'step'                    => 'object',
                     'rand'                    => $randItem,
                     'metademands_id'          => $this->fields["plugin_metademands_metademands_id"],
                     'link_to_user'            => $this->fields["link_to_user"],
                     'change_type'             => 1];
        Ajax::updateItemOnSelectEvent('dropdown_type' . $randType, "show_item", PLUGIN_METADEMANDS_WEBDIR .
                                                                              "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsType);
        echo "<span id='show_item_title' style='display:none'>";
       //      echo Html::script('/lib/jqueryplugins/spectrum-colorpicker/spectrum.js');
       //      echo Html::css('lib/jqueryplugins/spectrum-colorpicker/spectrum.min.css');
       //      Html::requireJs('colorpicker');
       //      $rand = mt_rand();
        Html::showColorField('color', ['value' => $this->fields["color"]]);
        echo "</span>";

        $paramsItem = ['value'                   => '__VALUE__',
                     'item'                    => '__VALUE__',
                     'type'                    => $this->fields['type'],
                     'task_link'               => $this->fields['plugin_metademands_tasks_id'],
                     'fields_link'             => $this->fields['fields_link'],
                     'max_upload'              => $this->fields['max_upload'],
                     'regex'                   => $this->fields['regex'],
                     'use_date_now'            => $this->fields['use_date_now'],
                     'additional_number_day'   => $this->fields['additional_number_day'],
                     'display_type'            => $this->fields['display_type'],
                     'informations_to_display' => $this->fields['informations_to_display'],
                     //                     'fields_display' => $this->fields['fields_display'],
                     'hidden_link'             => $this->fields['hidden_link'],
                     'hidden_block'            => $this->fields['hidden_block'],
                     'childs_blocks'           => $this->fields['childs_blocks'],
                     'users_id_validate'       => $this->fields['users_id_validate'],
                     'metademands_id'          => $this->fields["plugin_metademands_metademands_id"],
                     'custom_values'           => $this->fields["custom_values"],
                     'comment_values'          => $this->fields["comment_values"],
                     'default_values'          => $this->fields["default_values"],
                     'link_to_user'            => $this->fields["link_to_user"],
                     'check_value'             => $this->fields['check_value']];
        Ajax::updateItemOnSelectEvent('dropdown_item' . $randItem, "show_values", PLUGIN_METADEMANDS_WEBDIR .
                                                                                "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsItem);


        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $this->fields["plugin_metademands_metademands_id"]]);

        $params = ['id'                 => 'dropdown_type' . $randType,
                 'to_change'          => 'dropdown_item' . $randItem,
                 'value'              => 'dropdown',
                 'value2'             => 'dropdown_object',
                 'value3'             => 'dropdown_meta',
                 'value4'             => 'dropdown_multiple',
                 'current_item'       => $this->fields['item'],
                 'current_type'       => $this->fields['type'],
                 'titleDisplay'       => 'show_item_object',
                 'valueDisplay'       => 'show_item',
                 'titleDisplay_title' => 'show_item_label_title',
                 'valueDisplay_title' => 'show_item_title',
                 'value_title'        => 'title',
                 'value_informations' => 'informations',
                 'value_title_block'  => 'title-block',
        ];

        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(['root_doc' => PLUGIN_METADEMANDS_WEBDIR]) . ");";
        $script .= "metademandWizard.metademands_show_field_onchange(" . json_encode($params) . ");";
        $script .= "metademandWizard.metademands_show_field(" . json_encode($params) . ");";
        echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');

        echo "</td>";
        // Is_Basket Fields
        if ($metademand->fields['is_order'] == 1) {
            echo "<td>" . __('Display into the basket', 'metademands') . "</td>";
            echo "<td>";
            if ($ID > 0) {
                $value = $this->fields["is_basket"];
            } else {
                $value = 1;
            }
            Dropdown::showYesNo("is_basket", $value);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        if ($this->fields['type'] == "dropdown_object"
          && $this->fields["item"] == "Group") {
            $custom_values = self::_unserialize($this->fields['custom_values']);
            $is_assign     = $custom_values['is_assign'] ?? 0;
            $is_watcher    = $custom_values['is_watcher'] ?? 0;
            $is_requester  = $custom_values['is_requester'] ?? 0;
            $user_group  = $custom_values['user_group'] ?? 0;
            echo "<td></td>";

            echo "<td>";
            echo __('Requester');
            echo "&nbsp;";
            // Assigned group
            Dropdown::showYesNo('is_requester', $is_requester);
            echo "<br>";
            echo __('Watcher');
            echo "&nbsp;";
            // Watcher group
            Dropdown::showYesNo('is_watcher', $is_watcher);
            echo "<br>";
            echo __('Assigned');
            echo "&nbsp;";
            // Requester group
            Dropdown::showYesNo('is_assign', $is_assign);
            echo "<br>";
            echo __('My groups');
            echo "&nbsp;";
            // user_group
            Dropdown::showYesNo('user_group', $user_group);
            echo "</td>";
        } elseif ($this->fields['type'] == "dropdown_object"
                 && $this->fields["item"] == "User") {
            $custom_values = self::_unserialize($this->fields['custom_values']);
            $user_group  = $custom_values['user_group'] ?? 0;
            echo "<td></td>";

            echo "<td>";
            echo __('Only users of my groups', 'metademands');
            echo "&nbsp;";
            // user_group
            Dropdown::showYesNo('user_group', $user_group);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        //TODO permit linked items_id / itemtype
        if ($ID > 0
          && $this->fields['type'] != "title"
          && $this->fields['type'] != "title-block"
          && $this->fields['type'] != "informations") {
            echo "<td>";
            echo __('Use this field as object field', 'metademands');
            echo "</td>";
            echo "<td>";
            $ticket_fields[0] = Dropdown::EMPTY_VALUE;
            $objectclass      = $metademand->fields['object_to_create'];
            $searchOption     = Search::getOptions($objectclass);

            if ($objectclass == 'Ticket') {
                $tt = new TicketTemplate();
            } elseif ($objectclass == 'Problem') {
                $tt = new ProblemTemplate();
            } elseif ($objectclass == 'Change') {
                $tt = new ChangeTemplate();
            }
            $allowed_fields = $tt->getAllowedFields(true, true);

            unset($allowed_fields[-2]);

           //      Array ( [1] => name [21] => content [12] => status [10] => urgency [11] => impact [3] => priority
           //      [15] => date [4] => _users_id_requester [71] => _groups_id_requester [5] => _users_id_assign
           //      [8] => _groups_id_assign [6] => _suppliers_id_assign [66] => _users_id_observer [65] => _groups_id_observer
           //      [7] => itilcategories_id [131] => itemtype [13] => items_id [142] => _documents_id [175] => _tasktemplates_id [9] => requesttypes_id
           //      [83] => locations_id [37] => slas_id_tto [30] => slas_id_ttr [190] => olas_id_tto [191] => olas_id_ttr [18] => time_to_resolve
           //      [155] => time_to_own [180] => internal_time_to_resolve [185] => internal_time_to_own [45] => actiontime [52] => global_validation [14] => type )
           //         $granted_fields = [
           //            4,
           //            71,
           //            66,
           //            65,
           //            'urgency',
           //            'impact',
           //            'priority',
           //            'locations_id',
           //            'requesttypes_id',
           //            'itemtype',
           //            'items_id',
           //            'time_to_resolve',
           //         ];
            $granted_fields = [];
            if ($this->fields['type'] == "dropdown_object"
             && $this->fields["item"] == "User") {
                //Valideur
                $allowed_fields[59] = __('Approver');
                $granted_fields     = [
                4,
                66,
                59
                ];
            }
            if ($this->fields['type'] == "dropdown_object"
             && $this->fields["item"] == "Group") {
                $granted_fields = [
                71,
                65,
                ];
            }

            if ($this->fields['type'] == "dropdown"
             && $this->fields["item"] == "Location") {
                $granted_fields = [
                'locations_id',
                ];
            }

            if ($this->fields['type'] == "dropdown"
             && $this->fields["item"] == "RequestType") {
                $granted_fields = [
                'requesttypes_id',
                ];
            }

            if ($this->fields['type'] == "dropdown_meta"
             && ($this->fields["item"] == "urgency" || $this->fields["item"] == "impact" || $this->fields["item"] == "priority")) {
                $granted_fields = [
                $this->fields["item"]
                ];
            }

            if ($this->fields['type'] == "dropdown_meta"
              && ($this->fields["item"] == "ITILCategory_Metademands")) {
                $granted_fields = [
                  'itilcategories_id'
                ];
            }

            if ($this->fields['type'] == "date"
             || $this->fields["type"] == "datetime") {
                $granted_fields = [
                'time_to_resolve'
                ];
            }

            if (($this->fields['type'] == "dropdown_meta"
              && $this->fields["item"] == "mydevices")
             || ($this->fields['type'] == "dropdown_object"
                 && Ticket::isPossibleToAssignType($this->fields["item"]))) {
                $granted_fields = [
                13
                ];
            }

            foreach ($allowed_fields as $id => $value) {
                if (in_array($searchOption[$id]['linkfield'], $granted_fields) || in_array($id, $granted_fields)) {
                    $ticket_fields[$id] = $searchOption[$id]['name'];
                }
            }
            Dropdown::showFromArray(
                'used_by_ticket',
                $ticket_fields,
                ['value' => $this->fields["used_by_ticket"]]
            );
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
        echo "</tr>";

        if ($ID > 0 && $this->fields['type'] == "textarea") {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Use richt text', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('use_richtext', ($this->fields['use_richtext']));
            echo "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        }

        if ($ID > 0 && (
            ($this->fields['type'] == "dropdown_object"
                       && ($this->fields["item"] == "User"
                           || $this->fields["item"] == "Group"))
                      || ($this->fields['type'] == "dropdown"
                          && ($this->fields["item"] == "Location"
                              || $this->fields["item"] == "RequestType"))
                      || ($this->fields['type'] == "dropdown_meta"
                          && ($this->fields["item"] == "urgency"
                              || $this->fields["item"] == "impact"
                              || $this->fields["item"] == "priority"))
                      || $this->fields['type'] == "date"
                      || $this->fields["type"] == "datetime"
        )
        ) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            if ($metademand->fields['object_to_create'] == 'Ticket') {
                echo "<td>";
                echo __('Use this field for child ticket field', 'metademands');
                echo "</td>";
                echo "<td>";
                Dropdown::showYesNo('used_by_child', $this->fields['used_by_child']);
            } else {
                echo "<td colspan='2'>";
            }
            echo "</td>";
            echo "</tr>";
        } else {
            Html::hidden('used_by_child', ['value' => 0]);
        }

        if ($ID > 0 && ($this->fields['type'] == "dropdown_object"
                      && $this->fields["item"] == "User")) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "<td>";
            echo __('Use id of requester by default', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('default_use_id_requester', $this->fields['default_use_id_requester']);
            echo "</td>";
            echo "</tr>";
        }

        if ($ID > 0 && (
            ($this->fields['type'] == "dropdown_object"
                       && $this->fields["item"] == "Group")
                      || ($this->fields['type'] == "dropdown"
                          && $this->fields["item"] == "Location")
                      || ($this->fields['type'] == "dropdown_meta"
                          && $this->fields["item"] == "mydevices")
        )
        ) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "<td>";
            echo __('Link this to a user field', 'metademands');
            echo "</td>";
            echo "<td>";
            $arrayAvailable    = [];
            $arrayAvailable[0] = Dropdown::EMPTY_VALUE;
            $field             = new self();
            $fields            = $field->find(["plugin_metademands_metademands_id" => $this->fields['plugin_metademands_metademands_id'],
                                            'type'                              => "dropdown_object",
                                            "item"                              => User::getType()]);
            foreach ($fields as $f) {
                $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
            }
            Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $this->fields['link_to_user']]);
            echo "</td>";
            echo "</tr>";
        } else {
            Html::hidden('link_to_field', ['value' => 0]);
        }
        if (Plugin::isPluginActive('fields')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Link this to a plugin "fields" field', 'metademands');
            echo "</td>";
            echo "<td>";

            $arrayAvailableContainer = [];
            $fieldsContainer         = new PluginFieldsContainer();
            $fieldsContainers        = $fieldsContainer->find();

            $meta = new PluginMetademandsMetademand();
            $meta->getFromDB($this->fields["plugin_metademands_metademands_id"]);
            foreach ($fieldsContainers as $container) {
                $typesContainer = json_decode($container['itemtypes']);
                if (is_array($typesContainer) && in_array($meta->fields["object_to_create"], $typesContainer)) {
                    $arrayAvailableContainer[] = $container['id'];
                }
            }
         
            $pluginfield = new PluginMetademandsPluginfields();
            $opt = ['display_emptychoice' => true];
            if ($pluginfield->getFromDBByCrit(['plugin_metademands_fields_id' => $ID])) {
                $opt["value"] = $pluginfield->fields["plugin_fields_fields_id"];
            }
            $condition  = [];
            if (count($arrayAvailableContainer) > 0) {
                $condition  = ['plugin_fields_containers_id' => $arrayAvailableContainer];
            }

            $field = new PluginFieldsField();
            $fields_values = $field->find($condition);
            $datas = [];
            foreach ($fields_values as $fields_value) {
                $datas[$fields_value['id']] = $fields_value['label'];
            }

            Dropdown::showFromArray('plugin_fields_fields_id', $datas, $opt);

            echo "</td>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "</tr>";
        }


        echo "<tr class='tab_bg_1'>";
        // SHOW SPECIFIC VALUES
        echo "<td colspan='4'>";
        echo "<div id='show_values'>";
        $this->fields["dropdown"] = false;
       //      if ($this->fields['type'] == 'dropdown') {
       //         $this->fields['type'] = $this->fields['item'];
       //      }
        $paramTypeField = ['id'                      => $this->fields['id'],
                         'value'                   => $this->fields['type'],
                         'custom_values'           => $this->fields['custom_values'],
                         'comment_values'          => $this->fields['comment_values'],
                         'default_values'          => $this->fields['default_values'],
                         'task_link'               => $this->fields['plugin_metademands_tasks_id'],
                         'fields_link'             => $this->fields['fields_link'],
                         'max_upload'              => $this->fields['max_upload'],
                         'regex'                   => $this->fields['regex'],
                         'use_date_now'            => $this->fields['use_date_now'],
                         'additional_number_day'   => $this->fields['additional_number_day'],
                         'display_type'            => $this->fields['display_type'],
                         'informations_to_display' => $this->fields['informations_to_display'],
                         'hidden_link'             => $this->fields['hidden_link'],
                         'hidden_block'            => $this->fields['hidden_block'],
                         'childs_blocks'           => $this->fields['childs_blocks'],
                         'users_id_validate'       => $this->fields['users_id_validate'],
                         //                         'fields_display' => $this->fields['fields_display'],
                         'item'                    => $this->fields['item'],
                         'type'                    => $this->fields['type'],
                         'check_value'             => $this->fields['check_value'],
                         'drop'                    => $this->fields["dropdown"],
                         'link_to_user'            => $this->fields["link_to_user"],
                         'metademands_id'          => $this->fields["plugin_metademands_metademands_id"],
                         'checkbox_value'          => $this->fields["checkbox_value"],
                         'checkbox_id'             => $this->fields["checkbox_id"]];

        $this->getEditValue(
            self::_unserialize($this->fields['custom_values']),
            self::_unserialize($this->fields['comment_values']),
            self::_unserialize($this->fields['default_values']),
            $paramTypeField
        );
        $this->viewTypeField($paramTypeField);
        echo "</div>";
        echo "</td>";
        echo "</tr>";

        if ($ID > 0) {
            $this->showFormButtons(['colspan' => 2]);
        } else {
            if ($canedit) {
                echo "<tr class='tab_bg_1'>";
                echo "<td class='tab_bg_2 center' colspan='6'>";
                echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
                echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
                echo "</td>";
                echo "</tr>";
            }

            echo "</table>";
            Html::closeForm();
            echo "</div>";

            // Show fields
            $this->listFields($item->fields['id'], $canedit);

            // Show wizard demo
            $wizard = new PluginMetademandsWizard();
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th class='tab_bg_1'>" . PluginMetademandsWizard::getTypeName() . "</th></tr>";
            $meta = new PluginMetademandsMetademand();
            if ($meta->getFromDB($item->fields['id'])) {
                if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                    $background_color = $meta->fields['background_color'];
                }
            }
            echo "<tr><td style='background-color: " . $background_color . ";'>";
            $options = ['step'           => PluginMetademandsMetademand::STEP_SHOW,
                     'metademands_id' => $item->getID(),
                     'preview'        => true];
            $wizard->showWizard($options);
            echo "</td></tr>";
            echo "</table>";
        }
        return true;
    }

   /**
    * @param $plugin_metademands_metademands_id
    * @param $canedit
    *
    * @throws \GlpitestSQLError
    */
    private function listFields($plugin_metademands_metademands_id, $canedit)
    {
        $data = $this->find(
            ['plugin_metademands_metademands_id' => $plugin_metademands_metademands_id],
            ['rank', 'order']
        );
        $rand = mt_rand();

        if (count($data)) {
            echo "<div class='center first-bloc left'>";
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['item'      => __CLASS__,
                                    'container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th class='center b' colspan='11'>" . __('Form fields', 'metademands') . "</th>";
            echo "</tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<th width='10'>";
            if ($canedit) {
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            }
            echo "</th>";
            echo "<th class='center b'>" . __('Label') . "</th>";
            echo "<th class='center b'>" . __('Type') . "</th>";
            echo "<th class='center b'>" . __('Object', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Mandatory field') . "</th>";
            echo "<th class='center b'>" . __('Link a task to the field', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Value to check', 'metademands') . "</th>";
            $meta = new PluginMetademandsMetademand();
            if ($meta->getFromDB($plugin_metademands_metademands_id) && $meta->fields['is_order'] == 1) {
                echo "<th class='center b'>" . __('Display into the basket', 'metademands') . "</th>";
            }
            echo "<th class='center b'>" . __('Use this field as a ticket field', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Block', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Order', 'metademands') . "</th>";
            echo "</tr>";
            // Init navigation list for field items
            Session::initNavigateListItems($this->getType(), self::getTypeName(1));

            foreach ($data as $id => $value) {
                Session::addToNavigateListItems($this->getType(), $id);
                echo "<tr class='tab_bg_1'>";
                echo "<td width='10'>";
                if ($canedit) {
                    Html::showMassiveActionCheckBox(__CLASS__, $id);
                }
                echo "</td>";
                $name = $value['name'];
                echo "<td><a href='" . Toolbox::getItemTypeFormURL(__CLASS__) . "?id=" . $id . "'>";
                if (empty(trim($name))) {
                    echo __('ID') . " - " . $id;
                } else {
                    echo $name;
                }
                echo "</a></td>";
                echo "<td>" . self::getFieldTypesName($value['type']);
                //name of parent field
                if ($value['type'] == 'parent_field') {
                    $field = new self();
                    $field->getFromDB($value['parent_field_id']);
                    if (empty(trim($field->fields['name']))) {
                        echo " ( ID - " . $value['parent_field_id'] . ")";
                    } else {
                        echo " (" . $field->fields['name'] . ")";
                    }
                }
                echo "</td>";
                echo "<td>" . self::getFieldItemsName($value['item']) . "</td>";
                echo "<td>" . Dropdown::getYesNo($value['is_mandatory']) . "</td>";

                echo "<td>";

                $tasks = "";
                if (!empty($value['plugin_metademands_tasks_id'])) {
                    $tasks = json_decode($value['plugin_metademands_tasks_id']);
                }
                if (is_array($tasks)) {
                    foreach ($tasks as $k => $task) {
                        echo Dropdown::getDropdownName('glpi_plugin_metademands_tasks', $task);
                        echo "<br>";
                    }
                } else {
                    echo Dropdown::EMPTY_VALUE;
                }
                echo "</td>";

                echo "<td>";
                if (!empty($value['check_value'])) {
                    $check_value = self::_unserialize($value['check_value']);
                    if (is_array($check_value)) {
                        if (count($check_value) > 1) {
                            echo __('Multiples', 'metademands');
                        } elseif (count($check_value) > 0) {
                            switch ($value['type']) {
                                case 'yesno':
                                    foreach ($check_value as $key => $val) {
                                        echo Dropdown::getYesNo($val);
                                    }
                                    break;
                                case 'dropdown':
                                case 'dropdown_object':
                                case 'dropdown_meta':
                                case 'checkbox':
                                case 'radio':
                                    echo __('Defined value', 'metademands');
                                    break;
                                default:
                                    echo Dropdown::EMPTY_VALUE;
                                    break;
                            }
                        } else {
                            echo Dropdown::EMPTY_VALUE;
                        }
                    } else {
                        switch ($value['type']) {
                            case 'date':
                            case 'datetime':
                            case 'date_interval':
                            case 'datetime_interval':
                            case 'dropdown':
                            case 'dropdown_object':
                            case 'dropdown_meta':
                            case 'checkbox':
                            case 'radio':
                                echo __('Not null value', 'metademands');
                                break;
                            default:
                                echo Dropdown::EMPTY_VALUE;
                                break;
                        }
                    }
                } else {
                    echo Dropdown::EMPTY_VALUE;
                }
                echo "</td>";
                if ($meta->fields['is_order'] == 1) {
                    echo "<td>" . Dropdown::getYesNo($value['is_basket']) . "</td>";
                }
                echo "<td>";

                $searchOption = Search::getOptions('Ticket');
                if (isset($searchOption[$value['used_by_ticket']]['name'])) {
                    echo $searchOption[$value['used_by_ticket']]['name'];
                }
                echo "</td>";

                echo "<td class='center' style='color:white;background-color: #" . self::setColor($value['rank']) . "'>";
                echo $value['rank'] . "</td>";
                echo "<td class='center' style='color:white;background-color: #" . self::setColor($value['rank']) . "'>";
                echo empty($value['order']) ? __('None') : $value['order'];
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";

            if ($canedit && count($data)) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<div class='center first-bloc'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr  class='tab_bg_1'><td class='center'>" . __('No item to display') . "</td></tr>";
            echo "</table>";
        }
        echo "</div>";
    }

   /**
    * Show field types dropdown
    *
    * @param type  $name
    * @param array $param
    *
    * @return dropdown of types
    * @throws \GlpitestSQLError
    */
    public static function dropdownFieldTypes($name, $param = [])
    {
        global $PLUGIN_HOOKS;

        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        $type_fields = self::$field_types;

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginTextFieldItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    $type_fields = array_merge($type_fields, $new_fields);
                }
            }
        }

        foreach ($type_fields as $key => $types) {
            //delete type parent_field if no parent metademand & not field
            if ($types == 'parent_field') {
                $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask($p['metademands_id']);
                $list_fields        = [];
                $field              = new self();
                foreach ($metademands_parent as $parent_id) {
                    $condition    = ['plugin_metademands_metademands_id' => $parent_id,
                                ['NOT' => ['type' => ['parent_field', 'upload']]]];
                    $datas_fields = $field->find($condition, ['rank', 'order']);
                    foreach ($datas_fields as $data_field) {
                        $list_fields[$data_field['id']] = $data_field['name'];
                    }
                }

                if (count($metademands_parent) == 0) {
                    continue;
                } elseif (count($list_fields) == 0) {
                    continue;
                }
            }
            if (empty($types)) {
                $options[$key] = self::getFieldTypesName($types);
            } else {
                $options[$types] = self::getFieldTypesName($types);
            }
        }

        return Dropdown::showFromArray($name, $options, $p);
    }

   /**
    * get field types name
    *
    * @param string $value
    *
    * @return string types
    */
    public static function getFieldTypesName($value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($value) {
            case 'dropdown':
                return __('Dropdown');
            case 'dropdown_object':
                return __('Glpi Object', 'metademands');
            case 'dropdown_meta':
                return __('Dropdown', 'metademands');
            case 'dropdown_multiple':
                return __('Dropdown multiple', 'metademands');
            case 'text':
                return __('Text', 'metademands');
            case 'checkbox':
                return __('Checkbox', 'metademands');
            case 'textarea':
                return __('Textarea', 'metademands');
            case 'date':
                return __('Date', 'metademands');
            case 'datetime':
                return __('Date & Hour', 'metademands');
            case 'date_interval':
                return __('Date interval', 'metademands');
            case 'datetime_interval':
                return __('Date & Hour interval', 'metademands');
            case 'yesno':
                return __('Yes / No', 'metademands');
            case 'upload':
                return __('Add a document');
            case 'title':
                return __('Title');
            case 'title-block':
                return __('Block title', 'metademands');
            case 'radio':
                return __('Radio button', 'metademands');
            case 'parent_field':
                return __('Father\'s field', 'metademands');
            case 'link':
                return __('Link');
            case 'number':
                return __('Number', 'metademands');
            case 'informations':
                return __('Informations', 'metademands');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldItemsName($plug);
                        if (Plugin::isPluginActive($plug)
                        && is_array($new_fields)) {
                            if (isset($new_fields[$value])) {
                                return $new_fields[$value];
                            } else {
                                continue;
                            }
                            return Dropdown::EMPTY_VALUE;
                        }
                    }
                }
                return Dropdown::EMPTY_VALUE;
        }
    }


   /**
    * Load fields from plugins
    *
    * @param $plug
    */
    public static function addPluginFieldItems($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addFieldItems'])) {
                    return $item->addFieldItems();
                }
            }
        }
    }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
    public static function addPluginDropdownFieldItems($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addDropdownFieldItems'])) {
                    return $item->addDropdownFieldItems();
                }
            }
        }
    }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
    public static function addPluginDropdownMultipleFieldItems($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addDropdownMultipleFieldItems'])) {
                    return $item->addDropdownMultipleFieldItems();
                }
            }
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     *
     * @return void
     */
    public static function addPluginTextFieldItems($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addTextFieldItems'])) {
                    return $item->addTextFieldItems();
                }
            }
        }
    }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
    public static function getPluginFieldItemsName($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getFieldItemsName'])) {
                    return $item->getFieldItemsName();
                }
            }
        }
    }

   /**
    * Load data options saves from plugins
    *
    * @param $plug
    */
    public static function getPluginParamsOptions($plug, $params)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getParamsOptions'])) {
                    return $item->getParamsOptions($params);
                }
            }
        }
    }

   /**
    * show options fields from plugins
    *
    * @param $plug
    */
    public static function getPluginShowOptions($plug, $params)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'showOptions'])) {
                    return $item->showOptions($params);
                }
            }
        }
    }

   /**
    * saves data fields option from plugins
    *
    * @param $plug
    */
    public static function getPluginSaveOptions($plug, $params)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            if (Plugin::isPluginActive($plug)) {
                $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

                foreach ($pluginclasses as $pluginclass) {
                    if (!class_exists($pluginclass)) {
                        continue;
                    }
                    $form[$pluginclass] = [];
                    $item               = $dbu->getItemForItemtype($pluginclass);
                    if ($item && is_callable([$item, 'saveOptions'])) {
                        return $item->saveOptions($params);
                    }
                }
            }
        }
    }

   /**
    * Show field item dropdown
    *
    * @param type  $name
    * @param array $param
    *
    * @return dropdown of items
    */
    public static function dropdownFieldItems($name, $typefield, $param = [])
    {
        global $PLUGIN_HOOKS;

        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        $type_fields          = self::$dropdown_meta_items;
        $type_fields_multiple = self::$dropdown_multiple_items;
        switch ($typefield) {
            case "dropdown_multiple":
                foreach ($type_fields_multiple as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName($items);
                    } else {
                        $options[$items] = self::getFieldItemsName($items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownMultipleFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown":
                $options = Dropdown::getStandardDropdownItemTypes();
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown_meta":
                foreach ($type_fields as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName($items);
                    } else {
                        $options[$items] = self::getFieldItemsName($items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown_object":
                $options = self::getGlpiObject();
                return Dropdown::showFromArray($name, $options, $p);
                break;
        }
    }

   /**
    * get field items name
    *
    * @param string $value
    *
    * @return string item
    */
    public static function getFieldItemsName($value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($value) {
            case 'other':
                return __('Other');
            case 'ITILCategory_Metademands':
                return __('Category of the metademand', 'metademands');
            case 'mydevices':
                return __('My devices');
            case 'urgency':
                return __('Urgency');
            case 'impact':
                return __('Impact');
            case 'priority':
                return __('Priority');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldItemsName($plug);
                        if (Plugin::isPluginActive($plug)
                        && is_array($new_fields)) {
                            if (isset($new_fields[$value])) {
                                return $new_fields[$value];
                            } else {
                                continue;
                            }
                            return Dropdown::EMPTY_VALUE;
                        }
                    }
                }
                $dbu = new DbUtils();
                if (!is_numeric($value)) {
                    if ($item = $dbu->getItemForItemtype($value)) {
                        if (is_callable([$item, 'getTypeName'])) {
                            return $item::getTypeName();
                        }
                    }
                }
                return Dropdown::EMPTY_VALUE;
        }
    }


   /**
    * Load fields from plugins
    *
    * @param $plug
    */
    public static function getPluginFieldItemsType($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getFieldItemsType'])) {
                    return $item->getFieldItemsType();
                }
            }
        }
    }


   /**
    * @param        $data
    * @param        $metademands_data
    * @param bool   $preview
    * @param string $config_link
    * @param int    $itilcategories_id
    */
    public static function getFieldType($metademands_data, $data, $preview = false, $config_link = "", $itilcategories_id = 0)
    {
        global $PLUGIN_HOOKS;

        $required = "";
        if ($data['is_mandatory'] == 1 && $data['type'] != 'parent_field') {
            $required = "required=required style='color:red'";
        }

        $upload = "";
        if ($data['type'] == "upload") {
            $max = "";
            if ($data["max_upload"] > 0) {
                $max = "( " . sprintf(__("Maximum number of documents : %s ", "metademands"), $data["max_upload"]) . ")";
            }

            $upload = "$max (" . Document::getMaxUploadSize() . ")";
        }
        if ($data['is_mandatory'] == 1) {
            $required = "style='color:red'";
        }

        if (empty($label = self::displayField($data['id'], 'name'))) {
            $label = $data['name'];
        }
        if ($data["use_date_now"] == true) {
            if ($data["type"] == 'date' ||
             $data["type"] == 'date_interval'
            ) {
                $date          = date("Y-m-d");
                $addDays       = $data['additional_number_day'];
                $data['value'] = date('Y-m-d', strtotime($date . " + $addDays days"));
            }
            if ($data["type"] == 'datetime' ||
             $data["type"] == 'datetime_interval'
            ) {
                $addDays       = $data['additional_number_day'];
                $startDate     = time();
                $data['value'] = date('Y-m-d H:i:s', strtotime("+$addDays day", $startDate));
            }
        }
        if ($data['hide_title'] == 0) {
            echo "<label for='field[" . $data['id'] . "]' $required class='col-form-label'>";
            echo $label . " $upload";
            if ($preview) {
                echo $config_link;
            }
            echo "</label>";

            if (empty($comment = self::displayField($data['id'], 'comment'))) {
                $comment = $data['comment'];
            }
            if ($data['type'] != "title"
             && $data['type'] != "informations"
             && $data['type'] != "title-block"
             && $data['type'] != "text"
             && !empty($comment)) {
                $display = true;
                if ($data['use_richtext'] == 0) {
                    $display = false;
                }
                if ($display) {
                    echo "&nbsp;";
                    echo Html::showToolTip(Glpi\RichText\RichText::getSafeHtml($comment), ['display' => false]);
                }
            }
            echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $data['id'] . "'>";
            if ($data['is_mandatory'] == 1
             && $data['type'] != 'parent_field') {
                echo "*";
            }
            echo "</span>";

            echo "&nbsp;";

            //use plugin fields types
            if (isset($PLUGIN_HOOKS['metademands'])) {
                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                    $new_fields = self::getPluginFieldItemsType($plug);
                    if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                        if (in_array($data['type'], array_keys($new_fields))) {
                            $data['type'] = $new_fields[$data['type']];
                        }
                    }
                }
            }

            // Input
            echo "<br>";
        } else {
            if ($preview) {
                echo $config_link;
            }
        }
        echo self::getFieldInput($metademands_data, $data, false, $itilcategories_id, 0);
    }


   /**
    * @param      $metademands_data
    * @param      $data
    * @param bool $on_basket
    * @param int  $itilcategories_id
    *
    * @param int  $idline
    *
    * @return int|mixed|String
    */
    public static function getFieldInput($metademands_data, $data, $on_basket = false, $itilcategories_id = 0, $idline = 0)
    {
        global $DB;

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        $field = '';
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }

        if ($on_basket == false) {
            $namefield = 'field';
        } else {
            $namefield = 'field_basket_' . $idline;
        }

        if (empty($comment = self::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (empty($label2 = self::displayField($data['id'], 'label2'))) {
            $label2 = $data['label2'];
        }

        switch ($data['type']) {
            case 'dropdown_multiple':
                if ($data['item'] == User::getType()) {
                    $self                                                = new self();
                    $data['custom_values']                               = [];
                    $criteria                                            = $self->getDistinctUserCriteria() + $self->getProfileJoinCriteria();
                    $criteria['FROM']                                    = User::getTable();
                    $criteria['WHERE'][User::getTable() . '.is_deleted'] = 0;
                    $criteria['WHERE'][User::getTable() . '.is_active']  = 1;
                    $criteria['ORDER']                                   = ['NAME ASC'];
                    $iterator                                            = $DB->request($criteria);

                    foreach ($iterator as $datau) {
                        $data['custom_values'][$datau['users_id']] = getUserName($datau['users_id']);
                    }

                    if (!empty($value) && !is_array($value)) {
                        $value = json_decode($value);
                    }
                    if (!is_array($value)) {
                        $value = [];
                    }
                    $name  = $namefield . "[" . $data['id'] . "][]";
                    $css   = Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/doubleform.css");
                    $field = "$css
                           <div class=\"row\">";
                    $field .= "<div class=\"zone\">
                                   <select name=\"from\" id=\"multiselect$namefield" . $data["id"] . "\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";

                    if (is_array($data['custom_values']) && count($data['custom_values']) > 0) {
                        foreach ($data['custom_values'] as $k => $val) {
                            if (!in_array($k, $value)) {
                                $field .= "<option value=\"$k\" >$val</option>";
                            }
                        }
                    }

                    $field .= "</select></div>";

                    $field .= " <div class=\"centralCol\" style='width: 3%;'>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightAll\" class=\"btn buttonColTop buttonCol\"><i class=\"fas fa-angle-double-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightSelected\" class=\"btn  buttonCol\"><i class=\"fas fa-angle-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-left\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftAll\" class=\"btn buttonCol\"><i class=\"fas fa-angle-double-left\"></i></button>
                               </div>";

                    $required = "";
                  //               if ($data['is_mandatory'] == 1) {
                  //                  $required = "required=required";
                  //               }
                    $field .= "<div class=\"zone\">
                                   <select class='form-select' $required name=\"$name\" id=\"multiselect$namefield" . $data["id"] . "_to\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";
                    if (is_array($value) && count($value) > 0) {
                        foreach ($value as $k => $val) {
                            $field .= "<option selected value=\"$val\" >" . getUserName($val) . "</option>";
                        }
                    }
                    $field .= "</select></div>";

                    $field .= "</div>";

                    $field .= '<script src="../lib/multiselect2/dist/js/multiselect.js" type="text/javascript"></script>
                           <script type="text/javascript">
                           jQuery(document).ready(function($) {
                                  $("#multiselect' . $namefield . $data["id"] . '").multiselect({
                                      search: {
                                          left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                          right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                      },
                                      keepRenderingSort: true,
                                      fireSearch: function(value) {
                                          return value.length > 2;
                                      },
                                      moveFromAtoB: function(Multiselect, $source, $destination, $options, event, silent, skipStack ) {
                                        let self = Multiselect;
                        
                                        $options.each(function(index, option) {
                                            let $option = $(option);
                        
                                            if (self.options.ignoreDisabled && $option.is(":disabled")) {
                                                return true;
                                            }
                        
                                            if ($option.is("optgroup") || $option.parent().is("optgroup")) {
                                                let $sourceGroup = $option.is("optgroup") ? $option : $option.parent();
                                                let optgroupSelector = "optgroup[" + self.options.matchOptgroupBy + "=\'" + $sourceGroup.prop(self.options.matchOptgroupBy) + "\']";
                                                let $destinationGroup = $destination.find(optgroupSelector);
                        
                                                if (!$destinationGroup.length) {
                                                    $destinationGroup = $sourceGroup.clone(true);
                                                    $destinationGroup.empty();
                        
                                                    $destination.move($destinationGroup);
                                                }
                        
                                                if ($option.is("optgroup")) {
                                                    let disabledSelector = "";
                        
                                                    if (self.options.ignoreDisabled) {
                                                        disabledSelector = ":not(:disabled)";
                                                    }
                        
                                                    $destinationGroup.move($option.find("option" + disabledSelector));
                                                } else {
                                                    $destinationGroup.move($option);
                                                }
                        
                                                $sourceGroup.removeIfEmpty();
                                            } else {
                                                $destination.move($option);
                                                //Color change when multiselect value is switch
                                                $destination[0].value = $options[index].value;
                                                let selected = $destination[0].selectedIndex;
                                                let destOption = $destination[0].options[selected];
                                                if(destOption.style.color!="red" && destOption.style.color!="green") {
                                                    if($destination[0].name=="from"){
                                                        destOption.style.color = "red";
                                                    } else{
                                                        destOption.style.color = "green";
                                                    }
                                                } else{
                                                    destOption.style.color="#555555";
                                                }
                                            }
                                        });                        
                                        return self;
                                          
                                      }
                                  });
                              });
                           </script>';
                } else {
                    if (!empty($data['custom_values'])) {
                        $data['custom_values'] = self::_unserialize($data['custom_values']);
                        foreach ($data['custom_values'] as $k => $val) {
                            if ($data['item'] != "other") {
                                $data['custom_values'][$k] = $data["item"]::getFriendlyNameById($k);
                            } else {
                                if (!empty($ret = self::displayField($data["id"], "custom" . $k))) {
                                    $data['custom_values'][$k] = $ret;
                                }
                            }
                        }

                        $defaults       = self::_unserialize($data['default_values']);
                        $default_values = [];
                        if ($defaults) {
                            foreach ($defaults as $k => $v) {
                                if ($v == 1) {
                                    $default_values[] = $k;
                                }
                            }
                        }
                      //                  sort($data['custom_values']);
                        if (!empty($value) && !is_array($value)) {
                            $value = json_decode($value);
                        }
                        $value = is_array($value) ? $value : $default_values;
                        if ($data["display_type"] != self::CLASSIC_DISPLAY) {
                            $name  = $namefield . "[" . $data['id'] . "][]";
                            $css   = Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/doubleform.css");
                            $field = "$css
                           <div class=\"row\">";
                            $field .= "<div class=\"zone\">
                                   <select class='form-select' name=\"from\" id=\"multiselect$namefield" . $data["id"] . "\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";

                            foreach ($data['custom_values'] as $k => $val) {
                                if (!in_array($k, $value)) {
                                    $field .= "<option value=\"$k\" >$val</option>";
                                }
                            }

                            $field .= "</select></div>";

                            $field .= " <div class=\"centralCol\" style='width: 3%;'>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightAll\" class=\"btn buttonColTop buttonCol\"><i class=\"fas fa-angle-double-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-left\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftAll\" class=\"btn buttonCol\"><i class=\"fas fa-angle-double-left\"></i></button>
                               </div>";

                            $required = "";
                           //                     if ($data['is_mandatory'] == 1) {
                           //                        $required = "required=required";
                           //                     }
                            $field .= "<div class=\"zone\">
                                   <select class='form-select' $required name=\"$name\" id=\"multiselect$namefield" . $data["id"] . "_to\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";
                            foreach ($data['custom_values'] as $k => $val) {
                                if (in_array($k, $value)) {
                                    $field .= "<option selected value=\"$k\" >$val</option>";
                                }
                            }

                            $field .= "</select></div>";

                            $field .= "</div>";

                            $field .= '<script src="../lib/multiselect2/dist/js/multiselect.js" type="text/javascript"></script>
                           <script type="text/javascript">
                           jQuery(document).ready(function($) {
                                  $("#multiselect' . $namefield . $data["id"] . '").multiselect({
                                      search: {
                                          left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                          right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                      },
                                      keepRenderingSort: true,
                                      fireSearch: function(value) {
                                          return value.length > 2;
                                      },
                                      moveFromAtoB: function(Multiselect, $source, $destination, $options, event, silent, skipStack ) {
                                        let self = Multiselect;
                        
                                        $options.each(function(index, option) {
                                            let $option = $(option);
                        
                                            if (self.options.ignoreDisabled && $option.is(":disabled")) {
                                                return true;
                                            }
                        
                                            if ($option.is("optgroup") || $option.parent().is("optgroup")) {
                                                let $sourceGroup = $option.is("optgroup") ? $option : $option.parent();
                                                let optgroupSelector = "optgroup[" + self.options.matchOptgroupBy + "=\'" + $sourceGroup.prop(self.options.matchOptgroupBy) + "\']";
                                                let $destinationGroup = $destination.find(optgroupSelector);
                        
                                                if (!$destinationGroup.length) {
                                                    $destinationGroup = $sourceGroup.clone(true);
                                                    $destinationGroup.empty();
                        
                                                    $destination.move($destinationGroup);
                                                }
                        
                                                if ($option.is("optgroup")) {
                                                    let disabledSelector = "";
                        
                                                    if (self.options.ignoreDisabled) {
                                                        disabledSelector = ":not(:disabled)";
                                                    }
                        
                                                    $destinationGroup.move($option.find("option" + disabledSelector));
                                                } else {
                                                    $destinationGroup.move($option);
                                                }
                        
                                                $sourceGroup.removeIfEmpty();
                                            } else {
                                                $destination.move($option);
                                                //Color change when multiselect value is switch
                                                $destination[0].value = $options[index].value;
                                                let selected = $destination[0].selectedIndex;
                                                let destOption = $destination[0].options[selected];
                                                if(destOption.style.color!="red" && destOption.style.color!="green") {
                                                    if($destination[0].name=="from"){
                                                        destOption.style.color = "red";
                                                    } else{
                                                        destOption.style.color = "green";
                                                    }
                                                } else{
                                                    destOption.style.color="#555555";
                                                }
                                            }
                                        });                        
                                        return self;
                                          
                                      }
                                  });
                              });
                           </script>';
                         //
                         //                     $field .= "<script src=\"../lib/multiselect2/dist/js/multiselect.min.js\" type=\"text/javascript\"></script>
                         //                           <script type=\"text/javascript\">
                         //                           jQuery(document).ready(function($) {
                         //                                  $('#multiselect$namefield" . $data["id"] . "').multiselect({
                         //                                      search: {
                         //                                          left: '<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol form-control\" placeholder=\"" . __('Search') . "...\" />',
                         //                                          right: '<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol form-control\" placeholder=\"" . __('Search') . "...\" />',
                         //                                      },
                         //                                      keepRenderingSort: true,
                         //                                      fireSearch: function(value) {
                         //                                          return value.length > 2;
                         //                                      },
                         //                                  });
                         //                              });
                         //                           </script>";
                        } else {
                            $field = Dropdown::showFromArray(
                                $namefield . "[" . $data['id'] . "]",
                                $data['custom_values'],
                                ['values'   => $value,
                                'width'    => '250px',
                                'multiple' => true,
                                'display'  => false,
                                'required' => ($data['is_mandatory'] ? "required" : "")
                                ]
                            );
                        }
                    }
                }
                break;
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
                switch ($data['item']) {
                    case 'other':
                        if (!empty($data['custom_values'])) {
                            $data['custom_values'] = array_merge([0 => Dropdown::EMPTY_VALUE], self::_unserialize($data['custom_values']));
                            foreach ($data['custom_values'] as $k => $val) {
                                if (!empty($ret = self::displayField($data["id"], "custom" . $k))) {
                                    $data['custom_values'][$k] = $ret;
                                }
                            }

                            $defaults = self::_unserialize($data['default_values']);

                            $default_values = "";
                            if ($defaults) {
                                foreach ($defaults as $k => $v) {
                                    if ($v == 1) {
                                        $default_values = $k;
                                    }
                                }
                            }
                            $value = !empty($value) ? $value : $default_values;
                          //                     ksort($data['custom_values']);
                            $field = "";
                            $field .= Dropdown::showFromArray(
                                $namefield . "[" . $data['id'] . "]",
                                $data['custom_values'],
                                ['value'    => $value,
                                'width'    => '200px',
                                'display'  => false,
                                'required' => ($data['is_mandatory'] ? "required" : ""),
                                ]
                            );
                        }
                        break;
                    case 'User':
                        $userrand = mt_rand();
                        $field    = "";

                        if ($on_basket == false) {
                            $paramstooltip
                            = ['value'          => '__VALUE__',
                            'id_fielduser'   => $data['id'],
                            'metademands_id' => $data['plugin_metademands_metademands_id']];

                            $toupdate[] = ['value_fieldname'
                                                   => 'value',
                                    'id_fielduser' => $data['id'],
                                    'to_update'    => "tooltip_user" . $data['id'],
                                    'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                                    'moreparams'   => $paramstooltip];

                            $field .= "<script type='text/javascript'>";
                            $field .= "$(function() {";
                            Ajax::updateItemJsCode(
                                "tooltip_user" . $data['id'],
                                PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                                $paramstooltip,
                                $namefield . "[" . $data['id'] . "]",
                                false
                            );
                            $field .= "});</script>";
                        }
                        $paramsloc
                        = ['value'          => '__VALUE__',
                        'id_fielduser'   => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                        $toupdate[] = ['value_fieldname'
                                                => 'value',
                                 'id_fielduser' => $data['id'],
                                 'to_update'    => "location_user" . $data['id'],
                                 'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ulocationUpdate.php",
                                 'moreparams'   => $paramsloc];

                        $field .= "<script type='text/javascript'>";
                        $field .= "$(function() {";
                        Ajax::updateItemJsCode(
                            "location_user" . $data['id'],
                            PLUGIN_METADEMANDS_WEBDIR . "/ajax/ulocationUpdate.php",
                            $paramsloc,
                            $namefield . "[" . $data['id'] . "]",
                            false
                        );
                        $field .= "});</script>";


                        $paramsgroup
                         = ['value'          => '__VALUE__',
                         'id_fielduser'   => $data['id'],
                         'metademands_id' => $data['plugin_metademands_metademands_id']];

                        $toupdate[] = ['value_fieldname'
                                                => 'value',
                                 'id_fielduser' => $data['id'],
                                 'to_update'    => "group_user" . $data['id'],
                                 'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ugroupUpdate.php",
                                 'moreparams'   => $paramsgroup];

                        $field .= "<script type='text/javascript'>";
                        $field .= "$(function() {";
                        Ajax::updateItemJsCode(
                            "group_user" . $data['id'],
                            PLUGIN_METADEMANDS_WEBDIR . "/ajax/ugroupUpdate.php",
                            $paramsgroup,
                            $namefield . "[" . $data['id'] . "]",
                            false
                        );
                        $field .= "});</script>";

                        $paramsdev
                         = ['value'          => '__VALUE__',
                         'id_fielduser'   => $data['id'],
                         'metademands_id' => $data['plugin_metademands_metademands_id']];

                        $toupdate[] = ['value_fieldname'
                                                => 'value',
                                 'id_fielduser' => $data['id'],
                                 'to_update'    => "mydevices_user" . $data['id'],
                                 'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/umydevicesUpdate.php",
                                 'moreparams'   => $paramsdev];

                        $field .= "<script type='text/javascript'>";
                        $field .= "$(function() {";
                        Ajax::updateItemJsCode(
                            "mydevices_user" . $data['id'],
                            PLUGIN_METADEMANDS_WEBDIR . "/ajax/umydevicesUpdate.php",
                            $paramsdev,
                            $namefield . "[" . $data['id'] . "]",
                            false
                        );
                        $field .= "});</script>";

                        if (empty($value)) {
                            $value = ($data['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                        }

                        $right = "all";
                        if (!empty($data['custom_values'])) {
                            $options = PluginMetademandsField::_unserialize($data['custom_values']);
                            if (isset($options['user_group']) && $options['user_group'] == 1) {
                                $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                                $group_user_data = Group_User::getUserGroups(Session::getLoginUserID(), $condition);

                                $requester_groups = [];
                                foreach ($group_user_data as $groups) {
                                    $requester_groups[] = $groups['id'];
                                }
                                if (count($requester_groups) > 0) {
                                    $right = "groups";
                                }
                            }
                        }

                        $opt = ['name'     => $namefield . "[" . $data['id'] . "]",
                          'entity'   => $_SESSION['glpiactiveentities'],
                          'right'    => $right,
                          'rand'     => $userrand,
                          'value'    => $value,
                          'display'  => false,
                          'toupdate' => $toupdate,
                        ];
                        if ($data['is_mandatory'] == 1) {
                            $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                        }
                        echo User::dropdown($opt);
                       //                  $config = PluginMetademandsConfig::getInstance();
                       //                  if ($config['show_requester_informations'] && $on_basket == false) {
                       //
                       //                     echo "<div id='tooltip_user" . $data['id'] . "'>";
                       //                     $_POST['value']        = $value;
                       //                     $_POST['id_fielduser'] = $data['id'];
                       //                     include(PLUGIN_METADEMANDS_DIR . "/ajax/utooltipUpdate.php");
                       //                     echo "</div>";
                       //                  }

                        break;
                    case 'Group':
                        $field = "";
                        $cond  = [];
                        $_POST['field'] = $namefield . "[" . $data['id'] . "]";

                        if ($data['link_to_user'] > 0) {
                            echo "<div id='group_user" . $data['link_to_user'] . "' class=\"input-group\">";
                            $_POST['groups_id'] = $value;
                            $fieldUser          = new self();
                            $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                                                  'type' => "dropdown_object",
                                                  'item' => User::getType()]);

                            $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                                               && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                            $_POST['id_fielduser'] = $data['link_to_user'];
                            $_POST['fields_id']    = $data['id'];
                            $_POST['is_mandatory'] = $data['is_mandatory'] ?? 0;
                            include(PLUGIN_METADEMANDS_DIR . "/ajax/ugroupUpdate.php");
                            echo "</div>";
                        } else {
                            $name = $namefield . "[" . $data['id'] . "]";

                            if (!empty($data['custom_values'])) {
                                $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                                                   && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                                $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                                $group_user_data = Group_User::getUserGroups($_POST['value'], $condition);

                                $requester_groups = [];
                                foreach ($group_user_data as $groups) {
                                    $requester_groups[] = $groups['id'];
                                }
                                $options = PluginMetademandsField::_unserialize($data['custom_values']);

                                foreach ($options as $type_group => $values) {
                                    if ($type_group != 'user_group') {
                                        $cond[$type_group] = $values;
                                    } else {
                                        $cond["glpi_groups.id"] = $requester_groups;
                                    }
                                }
                                unset($cond['user_group']);
                            }
                            $val_group = (isset($_SESSION['plugin_metademands']['fields'][$data['id']])
                                   && !is_array($_SESSION['plugin_metademands']['fields'][$data['id']])) ? $_SESSION['plugin_metademands']['fields'][$data['id']] : 0;

                            $opt = ['name'      => $name,
                             'entity'    => $_SESSION['glpiactiveentities'],
                             'value'     => $val_group,
                             'condition' => $cond,
                             'display'   => false];
                            if ($data['is_mandatory'] == 1) {
                                $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                            }

                            $field .= Group::dropdown($opt);
                        }


                        break;
                    case 'ITILCategory_Metademands':
                        if ($on_basket == false) {
                            $nameitil = 'field';
                        } else {
                            $nameitil = 'basket';
                        }
                        $values = json_decode($metademand->fields['itilcategories_id']);
                        //from Service Catalog
                        if ($itilcategories_id > 0) {
                            $value = $itilcategories_id;
                        }
                       //                  if (!empty($values) && count($values) == 1) {
                       //                     foreach ($values as $key => $val)
                       //                        $itilcategories_id = $val;
                       //                  }
                       //                  if ($itilcategories_id > 0) {
                       //                     // itilcat from service catalog
                       //                     $itilCategory = new ITILCategory();
                       //                     $itilCategory->getFromDB($itilcategories_id);
                       //                     $field = "<span>" . $itilCategory->getField('name');
                       //                     $field .= "<input type='hidden' name='" . $nameitil . "_type' value='" . $metademand->fields['type'] . "' >";
                       //                     $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id' value='" . $itilcategories_id . "' >";
                       //                     $field .= "<span>";
                       //                  } else {
                        $opt = ['name'      => $nameitil . "_plugin_servicecatalog_itilcategories_id",
                          'right'     => 'all',
                          'value'     => $value,
                          'condition' => ["id" => $values],
                          'display'   => false,
                            'class'   => 'form-select itilmeta'];
                        if ($data['is_mandatory'] == 1) {
                            $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                        }
                        $field = "";
                        $field .= ITILCategory::dropdown($opt);
                        $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id_key' value='" . $data['id'] . "' >";
                       //                  }
                        break;
                    case 'mydevices':
                        if ($on_basket == false) {
                            // My items
                            //TODO : used_by_ticket -> link with item's ticket
                            $field = "";

                            $_POST['field'] = $namefield . "[" . $data['id'] . "]";
                           //                     $users_id = 0;
                            if ($data['link_to_user'] > 0) {
                                echo "<div id='mydevices_user" . $data['link_to_user'] . "' class=\"input-group\">";
                                $fieldUser = new self();
                                $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                                                     'type' => "dropdown_object",
                                                     'item' => User::getType()]);

                                $_POST['value']        = ($fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                                $_POST['id_fielduser'] = $data['link_to_user'];
                                $_POST['fields_id']    = $data['id'];
                                include(PLUGIN_METADEMANDS_DIR . "/ajax/umydevicesUpdate.php");
                                echo "</div>";
                            } else {
                                $rand  = mt_rand();
                                $p     = ['rand'  => $rand,
                                  'name'  => $_POST["field"],
                                  'value' => $_SESSION['plugin_metademands']['fields'][$data['id']] ?? 0];
                                $field .= PluginMetademandsField::dropdownMyDevices(Session::getLoginUserID(), $_SESSION['glpiactiveentities'], 0, 0, $p, false);
                            }
                        } else {
                            $dbu      = new DbUtils();
                            $splitter = explode("_", $value);
                            if (count($splitter) == 2) {
                                $itemtype = $splitter[0];
                                $items_id = $splitter[1];
                            }
                            $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "' >";
                            if (isset($itemtype) && isset($items_id)) {
                                $field .= Dropdown::getDropdownName(
                                    $dbu->getTableForItemType($itemtype),
                                    $items_id
                                );
                            }
                        }
                        break;
                    case 'urgency':
                        $field  = "";
                        $ticket = new Ticket();
                        if ($itilcategories_id == 0) {
                            $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                            if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                                foreach ($itilcategories_id_array as $arr) {
                                    $itilcategories_id = $arr;
                                }
                            }
                        }
                        if ($itilcategories_id > 0) {
                            $meta_tt = $ticket->getITILTemplateToUse(0, $metademand->fields['type'], $itilcategories_id, $metademand->fields['entities_id']);
                            if (isset($meta_tt->predefined['urgency'])) {
                                $default_value    = $meta_tt->predefined['urgency'];
                                $options['value'] = $_SESSION['plugin_metademands']['fields'][$data['id']] ?? $default_value;
                            }
                        }
                        $options['name']     = $namefield . "[" . $data['id'] . "]";
                        $options['display']  = false;
                        $options['required'] = ($data['is_mandatory'] ? "required" : "");
                        $field               .= Ticket::dropdownUrgency($options);
                        break;
                    case 'impact':
                        $field  = "";
                        $ticket = new Ticket();
                        if ($itilcategories_id == 0) {
                            $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                            if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                                foreach ($itilcategories_id_array as $arr) {
                                    $itilcategories_id = $arr;
                                }
                            }
                        }
                        if ($itilcategories_id > 0) {
                            $meta_tt = $ticket->getITILTemplateToUse(0, $metademand->fields['type'], $itilcategories_id, $metademand->fields['entities_id']);
                            if (isset($meta_tt->predefined['impact'])) {
                                $default_value    = $meta_tt->predefined['impact'];
                                $options['value'] = $_SESSION['plugin_metademands']['fields'][$data['id']] ?? $default_value;
                            }
                        }
                        $options['name']     = $namefield . "[" . $data['id'] . "]";
                        $options['display']  = false;
                        $options['required'] = ($data['is_mandatory'] ? "required" : "");
                        $field               .= Ticket::dropdownImpact($options);
                        break;
                    case 'priority':
                        $field  = "";
                        $ticket = new Ticket();
                        if ($itilcategories_id == 0) {
                            $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                            if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                                foreach ($itilcategories_id_array as $arr) {
                                    $itilcategories_id = $arr;
                                }
                            }
                        }
                        if ($itilcategories_id > 0) {
                            $meta_tt = $ticket->getITILTemplateToUse(0, $metademand->fields['type'], $itilcategories_id, $metademand->fields['entities_id']);
                            if (isset($meta_tt->predefined['priority'])) {
                                $default_value    = $meta_tt->predefined['priority'];
                                $options['value'] = $_SESSION['plugin_metademands']['fields'][$data['id']] ?? $default_value;
                            }
                        }
                        $options['name']     = $namefield . "[" . $data['id'] . "]";
                        $options['display']  = false;
                        $options['required'] = ($data['is_mandatory'] ? "required" : "");
                        $field               .= Ticket::dropdownPriority($options);
                        break;
                    default:
                        $cond = [];

                        if (!empty($data['custom_values']) && $data['item'] == 'Group') {
                            $options = self::_unserialize($data['custom_values']);
                            foreach ($options as $k => $val) {
                                if (!empty($ret = self::displayField($data["id"], "custom" . $k))) {
                                    $options[$k] = $ret;
                                }
                            }
                            foreach ($options as $type_group => $val) {
                                $cond[$type_group] = $val;
                            }
                        }
                        $opt = ['value'     => $value,
                          'entity'    => $_SESSION['glpiactiveentities'],
                          'name'      => $namefield . "[" . $data['id'] . "]",
                          //                          'readonly'  => true,
                          'condition' => $cond,
                          'display'   => false];
                        if ($data['is_mandatory'] == 1) {
                            $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                        }
                        if (!($item = getItemForItemtype($data['item']))) {
                            break;
                        }
                        if ($data['item'] == "Location") {
                            if ($data['link_to_user'] > 0) {
                                echo "<div id='location_user" . $data['link_to_user'] . "' class=\"input-group\">";
                                $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                                $_POST['locations_id'] = $value;
                                $fieldUser             = new self();
                                $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                                                     'type' => "dropdown_object",
                                                     'item' => User::getType()]);

                                $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                                                  && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                                $_POST['id_fielduser'] = $data['link_to_user'];
                                $_POST['fields_id']    = $data['id'];
                                include(PLUGIN_METADEMANDS_DIR . "/ajax/ulocationUpdate.php");
                                echo "</div>";
                            } else {
                                $options['name']    = $namefield . "[" . $data['id'] . "]";
                                $options['display'] = false;
                                if ($data['is_mandatory'] == 1) {
                                    $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                                }
                                //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands']['fields'][$data['id']]
                                $options['value'] = $_SESSION['plugin_metademands']['fields'][$data['id']] ?? 0;
                                $field            .= Location::dropdown($options);
                            }
                        } else {
                            if ($data['item'] == "PluginResourcesResource") {
                                $opt['showHabilitations'] = true;
                            }
                            $container_class = new $data['item']();
                            $field           = "";
                            $field           .= $container_class::dropdown($opt);
                        }
                        break;
                }
                break;
            case 'text':
                $name = $namefield . "[" . $data['id'] . "]";
                $opt  = ['value'       => Html::cleanInputText(Toolbox::stripslashes_deep($value)),
                     'placeholder' => (!$comment == null) ? Glpi\RichText\RichText::getTextFromHtml($comment) : "",
                     'size'        => 40];
                if ($data['is_mandatory'] == 1) {
                    $opt['required'] = "required";
                }
                $field = Html::input($name, $opt);
                break;
            case 'informations':
                if ($on_basket == false) {
                    $field = "<label class='col-form-label'>" . htmlspecialchars_decode(stripslashes($comment)) . "</label>";
                }
                break;
            case 'link':
                if (!empty($data['custom_values'])) {
                    $data['custom_values'] = self::_unserialize($data['custom_values']);
                    foreach ($data['custom_values'] as $k => $val) {
                        if (!empty($ret = self::displayField($data["id"], "custom" . $k))) {
                            $data['custom_values'][$k] = $ret;
                        }
                    }
                    switch ($data['custom_values'][0]) {
                        case 'button':
                            $btnLabel = __('Link');
                            if (!empty($label2)) {
                                $btnLabel = $label2;
                            }

                            $field = "<input type='submit' class='submit btn btn-primary' value ='" . Toolbox::stripTags($btnLabel) . "' 
                     target='_blank' onclick=\"window.open('" . $data['custom_values'][1] . "','_blank');return false\">";

                            break;
                        case 'link_a':
                            $field = Html::link($data['custom_values'][1], $data['custom_values'][1], ['target' => '_blank']);
                            break;
                    }
                    $title = $namefield . "[" . $data['id'] . "]";
                    $field .= Html::hidden($title, ['value' => $data['custom_values'][1]]);
                }
                break;
            case 'checkbox':
                if (!empty($data['custom_values'])) {
                    $data['custom_values'] = self::_unserialize($data['custom_values']);
                    foreach ($data['custom_values'] as $k => $val) {
                        if (!empty($ret = self::displayField($data["id"], "custom" . $k))) {
                            $data['custom_values'][$k] = $ret;
                        }
                    }
                    $data['comment_values'] = self::_unserialize($data['comment_values']);
                    $defaults               = self::_unserialize($data['default_values']);
                    if (!empty($value)) {
                        $value = self::_unserialize($value);
                    }
                    $nbr    = 0;
                    $inline = "";
                    if ($data['row_display'] == 1) {
                        $inline = 'custom-control-inline';
                    }
                    $field = "";

                    $childs_blocks = [];
                    if (isset($data['childs_blocks'])) {
                        $childs_blocks = json_decode($data['childs_blocks'], true);
                    }

                    foreach ($data['custom_values'] as $key => $label) {
                        $field   .= "<div class='custom-control custom-checkbox $inline'>";
                        $checked = "";
                        if (isset($value[$key])) {
                            $checked = isset($value[$key]) ? 'checked' : '';
                        } elseif (isset($defaults[$key]) && $on_basket == false) {
                            $checked = ($defaults[$key] == 1) ? 'checked' : '';
                        }
                        $required = "";
                        if ($data['is_mandatory'] == 1) {
                            $required = "required=required";
                        }
                        $field .= "<input $required class='form-check-input' type='checkbox' check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                        $nbr++;
                        $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
                        if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                            $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                            $field .= Html::showToolTip(
                                Glpi\RichText\RichText::getSafeHtml($data['comment_values'][$key]),
                                ['awesome-class' => 'fa-info-circle',
                                'display'       => false]
                            );
                            $field .= "</span>";
                        }
                        $field .= "</div>";
                        if (isset($childs_blocks[$key])) {
                            $id     = $data['id'];
                            $script = "<script type='text/javascript'>";
                            $script .= "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                            $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').not(':checked')) { ";

                            foreach ($childs_blocks[$key] as $customvalue => $childs) {
                                $script .= self::getJStorersetFields($childs);
                               //                        if ($customvalue != $key) {
                               //                           foreach ($childs as $k => $v) {
                               //                        $script .= "$('div[bloc-id=\"bloc$childs\"]').find(':input').each(function() {
                               //                                     switch(this.type) {
                               //                                            case 'password':
                               //                                            case 'text':
                               //                                            case 'textarea':
                               //                                            case 'file':
                               //                                            case 'date':
                               //                                            case 'number':
                               //                                            case 'tel':
                               //                                            case 'email':
                               //                                                jQuery(this).val('');
                               //                                                break;
                               //                                            case 'select-one':
                               //                                            case 'select-multiple':
                               //                                                jQuery(this).val('0').trigger('change');
                               //                                                jQuery(this).val('0');
                               //                                                break;
                               //                                            case 'checkbox':
                               //                                            case 'radio':
                               //                                                this.checked = false;
                               //                                                break;
                               //                                        }
                               //                                    });";
                                $script .= "$('div[bloc-id=\"bloc$childs\"]').hide();";
                               //                           }
                               //                        }
                            }
                            $script .= "}";
                            $script .= "})";
                            $script .= "</script>";

                            $field .= $script;
                        }
                    }
                } else {
                    $checked = $value ? 'checked' : '';
                    $field   = "<input class='form-check-input' type='checkbox' name='" . $namefield . "[" . $data['id'] . "]' value='checkbox' $checked>";
                }
                break;

            case 'radio':
                if (!empty($data['custom_values'])) {
                    $data['custom_values'] = self::_unserialize($data['custom_values']);
                    foreach ($data['custom_values'] as $k => $val) {
                        if (!empty($ret = self::displayField($data["id"], "custom" . $k))) {
                            $data['custom_values'][$k] = $ret;
                        }
                    }
                    $data['comment_values'] = self::_unserialize($data['comment_values']);
                    $defaults               = self::_unserialize($data['default_values']);
                    if ($value != null) {
                        $value = self::_unserialize($value);
                    }
                    $nbr    = 0;
                    $inline = "";
                    if ($data['row_display'] == 1) {
                        $inline = 'custom-control-inline';
                    }
                    $field = "";

                    $childs_blocks = [];
                    if (isset($data['childs_blocks'])) {
                        $childs_blocks = json_decode($data['childs_blocks'], true);
                    }

                    foreach ($data['custom_values'] as $key => $label) {
                        $field .= "<div class='custom-control custom-radio $inline'>";

                        $checked = "";
                        if ($value != null && $value == $key) {
                            $checked = $value == $key ? 'checked' : '';
                        } elseif ($value == null && isset($defaults[$key]) && $on_basket == false) {
                            $checked = ($defaults[$key] == 1) ? 'checked' : '';
                        }
                        $required = "";
                        if ($data['is_mandatory'] == 1) {
                            $required = "required=required";
                        }
                        $field .= "<input $required class='form-check-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                        $nbr++;


                        $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
                        if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                            $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                            $field .= Html::showToolTip(
                                Glpi\RichText\RichText::getSafeHtml($data['comment_values'][$key]),
                                ['awesome-class' => 'fa-info-circle',
                                'display'       => false]
                            );
                            $field .= "</span>";
                        }

                        $field .= "</div>";
                       //                  if (isset($childs_blocks[$key])) {
                       //                     $id     = $data['id'];
                       //                     $script = "<script type='text/javascript'>";
                       //                     $script .= "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                       //                     //                     $script  .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').is(':checked')) { ";
                       //
                       //                     foreach ($childs_blocks as $customvalue => $childs) {
                       //                        if ($customvalue != $key) {
                       //                           foreach ($childs as $k => $v) {
                       //                              $script .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                       //                           }
                       //                        }
                       //                     }
                       //                     //                     $script .= "}";
                       //                     $script .= "})</script>";
                       //
                       //                     $field .= $script;
                       //                  }
                        if (isset($childs_blocks[$key])) {
                            $id     = $data['id'];
                            $script = "<script type='text/javascript'>";
                            $script .= "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                            $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').is(':checked')) { ";

                            foreach ($childs_blocks as $customvalue => $childs) {
                                if ($customvalue != $key) {
                                    foreach ($childs as $k => $v) {
                                        $script .= self::getJStorersetFields($v);
                                   //                              $script .= "$('div[bloc-id=\"bloc$v\"]').find(':input').each(function() {
                                   //                                     switch(this.type) {
                                   //                                            case 'password':
                                   //                                            case 'text':
                                   //                                            case 'textarea':
                                   //                                            case 'file':
                                   //                                            case 'select-one':
                                   //                                            case 'select-multiple':
                                   //                                            case 'date':
                                   //                                            case 'number':
                                   //                                            case 'tel':
                                   //                                            case 'email':
                                   //                                                jQuery(this).val('');
                                   //                                                break;
                                   //                                            case 'checkbox':
                                   //                                            case 'radio':
                                   //                                                this.checked = false;
                                   //                                                break;
                                   //                                        }
                                   //                                    });";
                                        $script .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                                    }
                                }
                            }
                            $script .= "}";
                            $script .= "})";
                            $script .= "</script>";

                            $field .= $script;
                        }
                    }
                }
                break;
            case 'textarea':
                $value    = Html::cleanPostForTextArea($value);
                $required = "";
                if ($data['is_mandatory'] == 1) {
                    $required = "required='required'";
                }
                if ($data['use_richtext'] == 1) {
                    $field = Html::textarea(['name'              => $namefield . "[" . $data['id'] . "]",
                                        'value'             => $value,
   //                                        'editor_id'         => $namefield . "[" . $data['id'] . "]",
                                        'enable_richtext'   => true,
                                        'enable_fileupload' => false,
                                        //TODO add param
                                        'enable_images'     => true,
                                        'display'           => false,
                                        'required'          => ($data['is_mandatory'] ? "required" : ""),
                                        'cols'              => 80,
                                        'rows'              => 3]);
                } else {
                    $field = "<textarea $required class='form-control' rows='3' cols='80' 
               placeholder=\"" . Glpi\RichText\RichText::getTextFromHtml($comment) . "\" 
               name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "]'>" . $value . "</textarea>";
                }
                break;
            case 'date':
            case 'date_interval':
                $field = "<span style='width: 50%!important;display: -webkit-box;'>";
                $field .= Html::showDateField($namefield . "[" . $data['id'] . "]", ['value'    => $value,
                                                                                 'display'  => false,
                                                                                 'required' => ($data['is_mandatory'] ? "required" : ""),
                                                                                 'size'     => 40
                ]);
                $field .= "</span>";
                break;
            case 'datetime_interval':
            case 'datetime':
                $field = "<span style='width: 50%!important;display: -webkit-box;'>";
                $field .= Html::showDateTimeField($namefield . "[" . $data['id'] . "]", ['value'    => $value,
                                                                                     'display'  => false,
                                                                                     'required' => ($data['is_mandatory'] ? "required" : ""),
                                                                                     'size'     => 40
                ]);
                $field .= "</span>";
                break;
            case 'number':
                $data['custom_values'] = self::_unserialize($data['custom_values']);
                $opt                   = ['value'         => $value,
                                      'min'           => ((isset($data['custom_values']['min']) && $data['custom_values']['min'] != "") ? $data['custom_values']['min'] : 0),
                                      'max'           => ((isset($data['custom_values']['max']) && $data['custom_values']['max'] != "") ? $data['custom_values']['max'] : 999999),
                                      'step'          => ((isset($data['custom_values']['step']) && $data['custom_values']['step'] != "") ? $data['custom_values']['step'] : 1),
                                      'display'       => false,
                ];
                if (isset($data["is_mandatory"]) && $data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => 'required', 'isnumber' => 'isnumber'];
                }
                $field = Dropdown::showNumber($namefield . "[" . $data['id'] . "]", $opt);
                break;
            case 'yesno':
                $option[1] = __('No');
                $option[2] = __('Yes');
                if ($value == "") {
                    $value = $data['custom_values'];
                }
                if (is_array($value)) {
                    $value = "";
                }
                $field = "";
                $field .= Dropdown::showFromArray($namefield . "[" . $data['id'] . "]", $option, ['value'               => $value,
                                                                                              'display_emptychoice' => false,
                                                                                              'class'               => '',
                                                                                              'required'            => ($data['is_mandatory'] ? "required" : ""),
                                                                                              'display'             => false]);
                break;
            case 'upload':
                $arrayFiles = json_decode($value, true);
                $field      = "";
                $nb         = 0;
                if ($arrayFiles != "") {
                    foreach ($arrayFiles as $k => $file) {
                        $field .= str_replace($file['_prefix_filename'], "", $file['_filename']);
                        $wiz   = new PluginMetademandsWizard();
                        $field .= "&nbsp;";
                        //own showSimpleForm for return (not echo)
                        $field .= self::showSimpleForm(
                            $wiz->getFormURL(),
                            'delete_basket_file',
                            _x('button', 'Delete permanently'),
                            ['id'                           => $k,
                            'metademands_id'               => $data['plugin_metademands_metademands_id'],
                            'plugin_metademands_fields_id' => $data['id'],
                            'idline'                       => $idline
                            ],
                            'fa-times-circle'
                        );
                        $field .= "<br>";
                        $nb++;
                    }
                    if ($data["max_upload"] > $nb) {
                        if ($data["max_upload"] > 1) {
                            $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                           'editor_id'     => '',
                                           'showtitle'     => false,
                                           'multiple'      => true,
                                           'display'       => false]);
                        } else {
                            $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                           'editor_id'     => '',
                                           'showtitle'     => false,
                                           'display'       => false
                                          ]);
                        }
                    }
                } else {
                    if ($data["max_upload"] > 1) {
                        $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                        'editor_id'     => '',
                                        'showtitle'     => false,
                                        'multiple'      => true,
                                        'display'       => false]);
                    } else {
                        $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                        'editor_id'     => '',
                                        'showtitle'     => false,
                                        'display'       => false
                                       ]);
                    }
                }

                $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$value'>";
                break;

            case 'parent_field':
                foreach ($metademands_data as $metademands_data_steps) {
                    foreach ($metademands_data_steps as $line_data) {
                        foreach ($line_data['form'] as $field_id => $field_value) {
                            if ($field_id == $data['parent_field_id']) {
                                $value_parent_field = '';
                                if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id']])) {
                                    $value_parent_field = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id']];
                                }

                                switch ($field_value['type']) {
                                    case 'dropdown_multiple':
                                        if (!empty($field_value['custom_values'])) {
                                            $value_parent_field = $field_value['custom_values'][$value_parent_field];
                                        }
                                        break;
                                    case 'dropdown':
                                    case 'dropdown_object':
                                    case 'dropdown_meta':
                                        if (!empty($field_value['custom_values'])
                                        && $field_value['item'] == 'other') {
                                            $value_parent_field = $field_value['custom_values'][$value_parent_field];
                                        } else {
                                            switch ($field_value['item']) {
                                                case 'User':
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                                    $user               = new User();
                                                    $user->getFromDB($value_parent_field);
                                                    $value_parent_field .= $user->getName();
                                                    break;
                                                default:
                                                    $dbu                = new DbUtils();
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                                    $value_parent_field .= Dropdown::getDropdownName(
                                                        $dbu->getTableForItemType($field_value['item']),
                                                        $value_parent_field
                                                    );
                                                    break;
                                            }
                                        }
                                        break;
                                    case 'checkbox':
                                        if (!empty($field_value['custom_values'])) {
                                            $field_value['custom_values'] = self::_unserialize($field_value['custom_values']);
                                            foreach ($field_value['custom_values'] as $k => $val) {
                                                if (!empty($ret = self::displayField($field_value["id"], "custom" . $k))) {
                                                    $field_value['custom_values'][$k] = $ret;
                                                }
                                            }
                                            $checkboxes = self::_unserialize($value_parent_field);

                                            $custom_checkbox    = [];
                                            $value_parent_field = "";
                                            foreach ($field_value['custom_values'] as $key => $label) {
                                                $checked = isset($checkboxes[$key]) ? 1 : 0;
                                                if ($checked) {
                                                    $custom_checkbox[]  .= $label;
                                                    $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='checkbox'>";
                                                }
                                            }
                                            $value_parent_field .= implode('<br>', $custom_checkbox);
                                        }
                                        break;

                                    case 'radio':
                                        if (!empty($field_value['custom_values'])) {
                                            $field_value['custom_values'] = self::_unserialize($field_value['custom_values']);
                                            foreach ($field_value['custom_values'] as $k => $val) {
                                                if (!empty($ret = self::displayField($field_value["id"], "custom" . $k))) {
                                                    $field_value['custom_values'][$k] = $ret;
                                                }
                                            }
                                            foreach ($field_value['custom_values'] as $key => $label) {
                                                if ($value_parent_field == $key) {
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$key' >";
                                                    $value_parent_field .= $label;
                                                    break;
                                                }
                                            }
                                        }
                                        break;

                                    case 'date':
                                        $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                        $value_parent_field .= Html::convDate($value_parent_field);
                                        break;

                                    case 'datetime':
                                        $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                        $value_parent_field .= Html::convDateTime($value_parent_field);
                                        break;

                                    case 'date_interval':
                                        $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                        if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"])) {
                                            $value_parent_field2 = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"];
                                            $value_parent_field  .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value_parent_field2 . "'>";
                                        } else {
                                            $value_parent_field2 = 0;
                                        }
                                        $value_parent_field .= Html::convDate($value_parent_field) . " - " . Html::convDate($value_parent_field2);
                                        break;

                                    case 'datetime_interval':
                                        $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                        if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"])) {
                                            $value_parent_field2 = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"];
                                            $value_parent_field  .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value_parent_field2 . "'>";
                                        } else {
                                            $value_parent_field2 = 0;
                                        }
                                        $value_parent_field .= Html::convDateTime($value_parent_field) . " - " . Html::convDateTime($value_parent_field2);
                                        break;
                                    case 'yesno':
                                        $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                        $value_parent_field .= Dropdown::getYesNo($value_parent_field);
                                        break;

                                    default:
                                        $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                }
                                $field = $value_parent_field;
                                break;
                            }
                        }
                    }
                }
                break;
        }
        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }


   /**
    * @param        $entity
    * @param        $userid
    * @param string $filter
    * @param bool   $first
    *
    * @return array|int|mixed
    */
    public static function getUserGroup($entity, $userid, $cond = '', $first = true)
    {
        global $DB;

        $dbu = new DbUtils();

        $where = [];
        if ($cond) {
            $where = $cond;
        }

        $query = ['FIELDS'     => ['glpi_groups' => ['id']],
                'FROM'       => 'glpi_groups_users',
                'INNER JOIN' => ['glpi_groups' => ['FKEY' => ['glpi_groups'       => 'id',
                                                              'glpi_groups_users' => 'groups_id']]],
                'WHERE'      => ['users_id' => $userid,
                                 $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
                                ] + $where];

        $rep = [];
        foreach ($DB->request($query) as $data) {
            if ($first) {
                return $data['id'];
            }
            $rep[] = $data['id'];
        }
        return ($first ? 0 : $rep);
    }

   /**
    * @param        $action
    * @param        $btname
    * @param        $btlabel
    * @param array  $fields
    * @param string $btimage
    * @param string $btoption
    * @param string $confirm
    *
    * @return string
    */
    public static function showSimpleForm(
        $action,
        $btname,
        $btlabel,
        array $fields = [],
        $btimage = '',
        $btoption = '',
        $confirm = ''
    ) {
        return Html::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
    }

   /**
    * View options for items or types
    *
    * @param array $options
    *
    * @return void
    * @throws \GlpitestSQLError
    */
    public function viewTypeField($options)
    {
        global $PLUGIN_HOOKS;

        $params['value']       = 0;
        $params['check_value'] = [];


        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $allowed_options_types = self::$allowed_options_types;
        $allowed_options_items = self::$allowed_options_items;
        $new_fields            = [];

       //      if (Plugin::isPluginActive('ldapfields')) {
       //         $ldapfields_containers = new PluginLdapfieldsContainer();
       //         $ldapfields            = $ldapfields_containers->find(['type' => 'dropdown', 'is_active' => true]);
       //         if (count($ldapfields) > 0) {
       //            foreach ($ldapfields as $ldapfield) {
       //               array_push($allowed_options_types, $ldapfield['name']);
       //            }
       //         }
       //      }

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                if (Plugin::isPluginActive($plug)) {
                    $new_fields = self::addPluginFieldItems($plug);
                    if (is_array($new_fields) && count($new_fields) > 0) {
                        $allowed_options_types = array_merge($allowed_options_types, $new_fields);
                    }
                }
            }
        }

        if ((isset($params['check_value'])
           || $params['value'] == 'upload'
           || $params['value'] == 'text')
          && (in_array($params['type'], $allowed_options_types)
              || in_array($params['item'], $allowed_options_items))) {
            $metademands = new PluginMetademandsMetademand();
            $metademands->getFromDB($options['metademands_id']);
            if (is_array($new_fields) && in_array($params['value'], $new_fields)) {
                $params['value'] = $params['type'];
            }
            if ($params["type"] === "dropdown") {
                $params['value'] = $params['type'];
            }
            $address = $_SERVER['HTTP_REFERER'] ?? "";
            if ((isset($_SESSION['glpilayout']) && in_array($_SESSION['glpilayout'], ['vsplit', 'classic']))) {
                $address = $_SERVER['REQUEST_URI'];
            }
            if (isset($params['value'])) {
                if (strpos($address, 'field.form.php') > 0) {
                    echo "<div id='show_type_fields'>";
                    echo "<table width='100%' class='metademands_show_values'>";
                    echo "<tr><th colspan='2'>" . __('Options', 'metademands') . "&nbsp;";
                    echo "<i class='fas fa-plus-circle pointer' id='addNewOpt'></i>";
                    echo "</th></tr>";

                   //               echo "<tr>";
                    $nb  = 0;
                    $url = 'field.form.php?id=' . $_GET['id'];
                    // Multi criterias

                    $opts = [];
                    if (isset($params['check_value'])
                    && !empty($params['check_value'])
                    && $params['check_value'] != self::$not_null) {
                        $opts = self::_unserialize($params['check_value']);
                    }
                    if (strpos($address, 'nbOpt=') > 0) {
                        $nb        = substr($address, strpos($address, 'nbOpt=') + 6);
                        $opts[$nb] = $nb;
                    }

                    if ($params["value"] == 'upload') {
                        echo "<tr><td>";
                        echo "<table class='metademands_show_custom_fields'>";
                        echo "<tr><td>";
                        echo __('Number of documents allowed', 'metademands');
                       //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                        echo '</td>';
                        echo "<td>";
                        $data[0] = Dropdown::EMPTY_VALUE;
                        for ($i = 1; $i <= 50; $i++) {
                            $data[$i] = $i;
                        }

                        echo Dropdown::showFromArray("max_upload", $data, ['value' => $params['max_upload'], 'display' => false]);
                        echo "</td></tr>";
                        echo "</table>";
                        echo "</td></tr>";
                    }
                    if ($params["value"] == 'text') {
                        echo "<tr><td>";
                        echo "<table class='metademands_show_custom_fields'>";
                        echo "<tr><td>";
                        echo __('Regex', 'metademands');
                       //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                        echo '</td>';
                        echo "<td>";
                        echo Html::input('regex', ['value' => $params["regex"], 'size' => 50]);
                        echo "</td></tr>";
                        echo "</table>";
                        echo "</td></tr>";
                    }

                    if ($params["value"] == 'date' ||
                    $params["value"] == 'datetime' ||
                    $params["value"] == 'date_interval' ||
                    $params["value"] == 'datetime_interval'
                    ) {
                        $check_values = self::_unserialize($params['check_value']);
                        if (is_array($check_values)) {
                            $check_value = array_shift($check_values);
                        }

                        if (!isset($check_value)) {
                            $check_value = "";
                        }
                        echo "<tr><td>";
                        echo "<table class='metademands_show_custom_fields'>";
                        echo "<tr><td>";
                        echo __('Day greater or equal to now', 'metademands');
                        echo "</td><td>";
                        $checked = '';
                        if (isset($check_value) && !empty($check_value)) {
                            $checked = 'checked';
                        }
                        echo "<input type='checkbox' name='check_value' value='[1]' $checked>";
                        echo "</td></tr>";
                        echo "<tr><td>";
                        echo __('Define the default date', 'metademands');
                       //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                        echo '</td>';
                        echo "<td>";

                        Dropdown::showYesNo('use_date_now', $params['use_date_now']);
                        echo "</td></tr>";

                        echo "<tr><td>";
                        echo __('Additional number day to the default date', 'metademands');
                       //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                        echo '</td>';
                        echo "<td>";
                        $optionNumber = [
                        'value' => $params['additional_number_day'],
                        'min'   => 0,
                        'max'   => 500,
                        ];

                        Dropdown::showNumber('additional_number_day', $optionNumber);
                        echo "</td></tr>";

                        echo "</table>";
                        echo "</td></tr>";
                    }
                    if ($params["type"] == 'dropdown_multiple') {
                        $disp                              = [];
                        $disp[self::CLASSIC_DISPLAY]       = __("Classic display", "metademands");
                        $disp[self::DOUBLE_COLUMN_DISPLAY] = __("Double column display", "metademands");
                        echo "<tr><td>";
                        echo "<table class='metademands_show_custom_fields'>";
                        echo "<tr><td>";
                        echo __('Display type of the field', 'metademands');
                       //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                        echo '</td>';
                        echo "<td>";

                        echo Dropdown::showFromArray("display_type", $disp, ['value' => $params['display_type'], 'display' => false]);
                        echo "</td></tr>";

                        if ($params["item"] == 'User') {
                            echo "<tr>";
                            echo "<td colspan='2' class='center'>";
                            echo __("Informations to display in ticket and PDF", "metademands");
                            echo "</td>";
                            echo "</tr>";
                            echo "<tr>";
                            echo "<td colspan='2' class='center'>";
                            $params['informations_to_display'] = json_decode($params['informations_to_display']) ?? [];

                            $informations["full_name"] = __('Complete name');
                            $informations["realname"]  = __('Surname');
                            $informations["firstname"] = __('First name');
                            $informations["name"]      = __('Login');
                           //                     $informations["group"]             = Group::getTypeName(1);
                            $informations["email"] = _n('Email', 'Emails', 1);
                            echo Dropdown::showFromArray('informations_to_display', $informations, ['values'   => $params['informations_to_display'],
                                                                                             'display'  => false,
                                                                                             'multiple' => true]);
                            echo "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "</td></tr>";
                    }
                    if ($params['type'] == 'dropdown_object' && $params['item'] == 'User') {
                        echo "<tr><td>";
                        echo "<table class='metademands_show_custom_fields'>";
                        echo "<tr>";
                        echo "<td colspan='2' class='center'>";
                        echo __("Informations to display in ticket and PDF", "metademands");
                        echo "</td>";
                        echo "</tr>";
                        echo "<tr>";
                        echo "<td colspan='2' class='center'>";
                        $params['informations_to_display'] = json_decode($params['informations_to_display']) ?? [];
                        $informations["full_name"]         = __('Complete name');
                        $informations["realname"]          = __('Surname');
                        $informations["firstname"]         = __('First name');
                        $informations["name"]              = __('Login');
                       //                  $informations["group"]             = Group::getTypeName(1);
                        $informations["email"] = _n('Email', 'Emails', 1);
                        echo Dropdown::showFromArray('informations_to_display', $informations, ['values'   => $params['informations_to_display'],
                                                                                          'display'  => false,
                                                                                          'multiple' => true]);
                        echo "</td>";
                        echo "</tr>";
                        echo "</table>";
                        echo "</td></tr>";
                    }
                    if (empty($opts)) {
                        $opts = [];
                    }
                    if (is_array($opts)) {
                        echo Html::hidden('nbOptions', ['id' => 'nbOptions', 'value' => count($opts)]);
                    }
                    if (is_array($opts) && count($opts) == 0) {
                        echo $this->addNewOpt($url);
                    } elseif (is_array($opts) && count($opts) > 0) {
                        echo "<tr><td>";
                        foreach ($opts as $k => $opt) {
                            echo "<table class='metademands_show_custom_fields'>";
                            echo $this->showOptions($metademands->getField('id'), $params, $k);
                            echo "</table>";
                            echo $this->addNewOpt($url);
                        }
                        echo "</td></tr>";
                    }
                    echo "</tbody></table>";
                    echo "</div>";
                }
            }
        }
    }

   /**
    * @param $url
    */
    public function addNewOpt($url)
    {
        $res = "<script type='text/javascript'>

      let root_metademands_doc = '" . PLUGIN_METADEMANDS_WEBDIR . "';
      
                $('#addNewOpt').click(function(){
                    let nb = document.getElementById('nbOptions').valueOf().value;
                    nb++;
                    parent.parent.window.location.replace(root_metademands_doc + '/front/" . $url . "&nbOpt='+nb);
                });
                </script>";
        echo $res;
    }

   /**
    * @param $metademands_id
    * @param $params
    * @param $optid
    *
    * @return string
    * @throws \GlpitestSQLError
    */
    public function showOptions($metademands_id, $params, $optid)
    {
        global $PLUGIN_HOOKS;

        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($metademands_id);

        $display = false;
        $html    = "";

        $params['check_value'] = self::_unserialize($params['check_value']);
        if (!isset($params['check_value'][$optid])) {
            $params['check_value'] = "";
        } else {
            $params['check_value'] = $params['check_value'][$optid];
        }

        $params['task_link'] = self::_unserialize($params['task_link']);
        if (!isset($params['task_link'][$optid])) {
            $params['task_link'] = "";
        } else {
            $params['task_link'] = $params['task_link'][$optid];
        }

        $params['fields_link'] = self::_unserialize($params['fields_link']);
        if (!isset($params['fields_link'][$optid])) {
            $params['fields_link'] = "";
        } else {
            $params['fields_link'] = $params['fields_link'][$optid];
        }

        $params['hidden_link'] = self::_unserialize($params['hidden_link']);
        if (!isset($params['hidden_link'][$optid])) {
            $params['hidden_link'] = "";
        } else {
            $params['hidden_link'] = $params['hidden_link'][$optid];
        }

        $params['hidden_block'] = self::_unserialize($params['hidden_block']);
        if (!isset($params['hidden_block'][$optid])) {
            $params['hidden_block'] = 0;
        } else {
            $params['hidden_block'] = $params['hidden_block'][$optid];
        }

        $params['childs_blocks'] = json_decode($params['childs_blocks'], true);

        if (isset($params['childs_blocks'][$optid])) {
            $params['childs_blocks'] = $params['childs_blocks'][$optid];
        } else {
            $params['childs_blocks'] = [];
        }

        $params['users_id_validate'] = self::_unserialize($params['users_id_validate']);
        if (!isset($params['users_id_validate'][$optid])) {
            $params['users_id_validate'] = 0;
        } else {
            $params['users_id_validate'] = $params['users_id_validate'][$optid];
        }
        $params['checkbox_id'] = self::_unserialize($params['checkbox_id']);
        if (!isset($params['checkbox_id'][$optid])) {
            $params['checkbox_id'] = 0;
        } else {
            $params['checkbox_id'] = $params['checkbox_id'][$optid];
        }
        $params['checkbox_value'] = self::_unserialize($params['checkbox_value']);
        if (!isset($params['checkbox_value'][$optid])) {
            $params['checkbox_value'] = 0;
        } else {
            $params['checkbox_value'] = $params['checkbox_value'][$optid];
        }

        //Hook to get values saves from plugin
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p                                      = [];
                $p["plugin_metademands_fields_id"]      = $this->getID();
                $p["plugin_metademands_metademands_id"] = $metademands_id;
                $p["nbOpt"]                             = $optid;

                $new_params = self::getPluginParamsOptions($plug, $p);
                if (Plugin::isPluginActive($plug)
                && is_array($new_params)) {
                    $params = array_merge($params, $new_params);
                }
            }
        }
        switch ($params['value']) {
            case 'yesno':
                $data[1] = __('No');
                $data[2] = __('Yes');
                // Value to check
                $html .= "<tr><td>";
                $html .= __('Value to check', 'metademands') . '</td><td>';
                $html .= Dropdown::showFromArray("check_value[]", $data, ['value'   => $params['check_value'],
                                                                      'display' => $display]);
               //            $html .= Dropdown::showYesNo("check_value[]", $params['check_value'],-1,['display' => $display]);
                $html .= "</td>";
                $html .= "</tr><td>";

                $html .= $this->showLinkHtml($metademands->fields["id"], $params, $optid, 1, 1, 1);
                break;
           //         case 'date' :
           //         case 'datetime' :
           //         case 'date_interval' :
           //         case 'datetime_interval' :
           //            $html    .= "<tr><td>";
           //            $html    .= __('Day greater or equal to now', 'metademands');
           //            $html    .= "</td><td>";
           //            $checked = '';
           //            if (isset($params['check_value']) && !empty($params['check_value'])) {
           //               $checked = 'checked';
           //            }
           //            $html .= "<input type='checkbox' name='check_value' value='[1]' $checked>";
           //            $html .= "</td></tr>";
           //
           //            $html .= "<tr><td>";
           //            $html .= __('Default date now', 'metademands');
           //            $html .= "</td><td>";
           //            $html .= Dropdown::showYesNo('use_date_now', $params['use_date_now'],-1,['display' => $display]);
           //            $html .= "</td></tr>";
           //
           //            $html         .= "<tr><td>";
           //            $html         .= __('Additional number day to the default date', 'metademands');
           //            $html         .= "</td><td>";
           //            $optionNumber = [
           //               'value' => $params['additional_number_day'],
           //               'min'   => 0,
           //               'max'   => 500,
           //               'display' => $display
           //            ];
           //            $html         .= Dropdown::showNumber('additional_number_day', $optionNumber);
           //            $html         .= "</td></tr>";
           //
           //            break;

            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'dropdown_multiple':
                $html .= "<tr><td>";
                $html .= __('Value to check', 'metademands');
                $html .= " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
                $html .= '</td>';
                $html .= '<td>';
                switch ($params["item"]) {
                    case 'ITILCategory_Metademands':
                        $metademand = new PluginMetademandsMetademand();
                        $metademand->getFromDB($metademands_id);
                        $values = json_decode($metademand->fields['itilcategories_id']);

                        $name = "check_value[]";
                        $opt  = ['name'      => $name,
                           'right'     => 'all',
                           'value'     => $params['check_value'],
                           'condition' => ["id" => $values],
                           'display'   => false];
                        $html .= ITILCategory::dropdown($opt);

                        break;
                    case 'User':
                        $userrand = mt_rand();
                        $name     = "check_value[]";
                        $html     .= User::dropdown(['name'    => $name,
                                               'entity'  => $_SESSION['glpiactiveentities'],
                                               'right'   => 'all',
                                               'rand'    => $userrand,
                                               'value'   => $params['check_value'],
                                               'display' => false
                                              ]);
                        break;
                    case 'Group':
                        $name = "check_value[]";
                        $cond = [];
                        if (!empty($params['custom_values'])) {
                            $options = PluginMetademandsField::_unserialize($params['custom_values']);
                            foreach ($options as $type_group => $values) {
                                $cond[$type_group] = $values;
                            }
                        }
                        $html .= Group::dropdown(['name'      => $name,
                                            'entity'    => $_SESSION['glpiactiveentities'],
                                            'value'     => $params['check_value'],
                                            //                                            'readonly'  => true,
                                            'condition' => $cond,
                                            'display'   => false
                                           ]);
                        break;
                    default:
                        $dbu = new DbUtils();
                        if ($item = $dbu->getItemForItemtype($params["item"])
                              && $params['value'] != "dropdown_multiple") {
                           //               if ($params['value'] == 'group') {
                           //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                           //               } else {
                            $name = "check_value[]";
                           //               }
                            $html .= $params['item']::Dropdown(["name"    => $name,
                                                         "value"   => $params['check_value'],
                                                         "display" => $display]);
                        } else {
                            if ($params["item"] != "other" && $params["value"] == "dropdown_multiple") {
                                $elements[0] = Dropdown::EMPTY_VALUE;
                                if (is_array(json_decode($params['custom_values'], true))) {
                                    $elements += json_decode($params['custom_values'], true);
                                }
                                foreach ($elements as $key => $val) {
                                    if ($key != 0) {
                                        $elements[$key] = $params["item"]::getFriendlyNameById($key);
                                    }
                                }
                            } else {
                                $elements[0] = Dropdown::EMPTY_VALUE;
                                if (is_array(json_decode($params['custom_values'], true))) {
                                    $elements += json_decode($params['custom_values'], true);
                                }
                                foreach ($elements as $key => $val) {
                                    $elements[$key] = urldecode($val);
                                }
                            }
                            $html .= Dropdown::showFromArray(
                                "check_value[]",
                                $elements,
                                ['value'   => $params['check_value'],
                                'display' => $display]
                            );
                        }
                        break;
                }


                $html .= "</td>";
                $html .= "</tr>";

                $html .= $this->showLinkHtml($metademands->fields["id"], $params, $optid, 1, 1, 1);

                break;
            case 'checkbox':
            case 'radio':
                // Value to check
                $html         .= "<tr><td>";
                $html         .= __('Value to check', 'metademands');
                $html         .= " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")" . '</td>';
                $html         .= '<td>';
                $elements[-1] = Dropdown::EMPTY_VALUE;
                if (is_array(json_decode($params['custom_values'], true))) {
                    $elements += json_decode($params['custom_values'], true);
                }
                foreach ($elements as $key => $val) {
                    $elements[$key] = urldecode($val);
                }
                $html .= Dropdown::showFromArray(
                    "check_value[]",
                    $elements,
                    ['value'   => $params['check_value'],
                    'display' => $display]
                );

                $html .= "</td>";
                $html .= "</tr><td>";

                $html .= $this->showLinkHtml($metademands->fields["id"], $params, $params['check_value'], 1, 1, 1);

                break;
            case 'parent_field':
                $html .= "<tr><td>";
                $html .= __('Field') . '</td>';
                $html .= '<td>';
                //list of fields
                $fields            = [];
                $metademand_parent = new PluginMetademandsMetademand();

                // list of parents
                $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask($metademands->fields["id"]);

                foreach ($metademands_parent as $parent_id) {
                    if ($metademand_parent->getFromDB($parent_id)) {
                        $name_metademand = $metademand_parent->getName();

                        $condition    = ['plugin_metademands_metademands_id' => $parent_id,
                                   ['NOT' => ['type' => ['parent_field', 'upload']]]];
                        $datas_fields = $this->find($condition, ['rank', 'order']);
                        //formatting the name to display (Name of metademand - Father's Field Label - type)
                        foreach ($datas_fields as $data_field) {
                            $fields[$data_field['id']] = $name_metademand . " - " . $data_field['name'] . " - " . self::getFieldTypesName($data_field['type']);
                        }
                    }
                }
                $html .= Dropdown::showFromArray('parent_field_id[]', $fields, ['display' => $display]);
                $html .= "</td></tr>";
                break;
            case 'text':
            case 'textarea':
                $data[1] = __('No');
                $data[2] = __('Yes');
                // Value to check
                $html .= "<tr><td>";
                $html .= __('If field empty', 'metademands') . '</td><td>';
                $html .= Dropdown::showFromArray("check_value[]", $data, ['value' => $params['check_value'], 'display' => $display]);
               //            $html .= Dropdown::showYesNo("check_value[]", $params['check_value'],-1,['display' => $display]);
                $html .= "</td>";
                $html .= "</tr><td>";

                $html .= $this->showLinkHtml($metademands->fields["id"], $params, $optid, 1, 0, 1);
                break;

            case 'upload':
                break;
        }

        return $html;
    }

   /**
    * @param     $metademands_id
    * @param     $params
    * @param     $opt
    * @param int $task
    * @param int $field
    * @param int $hidden
    *
    * @return string
    * @throws \GlpitestSQLError
    */

    public function showLinkHtml($metademands_id, $params, $opt, $task = 1, $field = 1, $hidden = 0)
    {
        global $PLUGIN_HOOKS;

        $res = "";

        // Show task link
        if ($task) {
            $res = '<tr><td>';
            $res .= __('Link a task to the field', 'metademands');
            $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
            $res .= '</td><td>';
            $res .= PluginMetademandsTask::showAllTasksDropdown($metademands_id, $params['task_link'], false);
            $res .= "</td></tr>";
        }

        // Show field link
        if ($field) {
            $res .= "<tr><td>";
            $res .= __('Link a field to the field', 'metademands');
            $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes mandatory', 'metademands') . '</span>';
            $res .= '</td>';
            $res .= "<td>";
            $res .= self::showFieldsDropdown($metademands_id, $params['fields_link'], $this->getID(), false);
            $res .= "</td></tr>";
        }
        if ($hidden) {
            $res .= "<tr><td>";
            $res .= __('Link a hidden field', 'metademands');
            $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes visible', 'metademands') . '</span>';
            $res .= '</td>';
            $res .= "<td>";
            $res .= self::showHiddenDropdown($metademands_id, $params['hidden_link'], $this->getID(), false);
            $res .= "</td></tr>";


            $res .= "<tr><td>";
            $res .= __('Link a hidden block', 'metademands');
            $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the block becomes visible', 'metademands') . '</span>';
            $res .= '</td>';
            $res .= "<td>";
            $res .= self::showHiddenBlockDropdown($params['hidden_block'], $this->getID(), false);
            $res .= "</td></tr>";

            if ($this->getField("type") == "checkbox"
             || $this->getField("type") == "radio"
             || $this->getField("type") == "text"
             || $this->getField("type") == "textarea"
             || $this->getField("type") == "group"
             || $this->getField("type") == "dropdown"
             || $this->getField("type") == "dropdown_object"
             || $this->getField("type") == "dropdown_meta"
             || $this->getField("type") == "yesno") {
                $res .= "<tr><td>";
                $res .= __('Childs blocks', 'metademands');
                $res .= '</br><span class="metademands_wizard_comments">' . __('If child blocks exist, these blocks are hidden when you deselect the option configured', 'metademands') . '</span>';
                $res .= '</td>';
                $res .= "<td>";
                $res .= self::showChildsBlocksDropdown($metademands_id, $params['childs_blocks'], $this->getID(), $opt, false);
                $res .= "</td></tr>";
            }
            if ($this->getField("type") == "checkbox") {
                $res .= "<tr><td>";
                $res .= __('Link a validation', 'metademands');
                $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the validation is sent to the user', 'metademands') . '</span>';
                $res .= '</td>';
                $res .= "<td>";
                $res .= self::showValidationDropdown($metademands_id, $params['users_id_validate'], $this->getID(), false);
                $res .= "</td></tr>";
            }
            if ($this->getField("type") == "dropdown_multiple") {
                $res .= "<tr><td>";
                $res .= __('Bind to the value of this checkbox', 'metademands');
                $res .= '</br><span class="metademands_wizard_comments">' . __('If the selected value is equal to the value to check, the checkbox value is set', 'metademands') . '</span>';
                $res .= '</td>';
                $res .= "<td>";
                $res .= self::showCheckBoxDropdown($metademands_id, $params['checkbox_value'], $params['checkbox_id'], false);
                $res .= "</td></tr>";
            }
        }

        //Hook to print new options from plugins
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p                                      = $params;
                $p["plugin_metademands_fields_id"]      = $this->getID();
                $p["plugin_metademands_metademands_id"] = $metademands_id;
                $p["hidden"]                            = $hidden;


                $new_res = self::getPluginShowOptions($plug, $p);
                if (Plugin::isPluginActive($plug)
                && !empty($new_res)) {
                    $res .= $new_res;
                }
            }
        }
        $res   .= "<tr><td colspan='2' class='center'>";
        $res   .= Html::hidden('clear_option', ['value' => "clear_option"]);
        $name  = "option[$opt]";
        $title = "<i class='fa-1x fas fa-trash' data-hasqtip='0' aria-hidden='true'></i>";
       //      $title .= _sx('button', 'Delete permanently');
        $res .= Html::submit($title, ['name' => $name, 'class' => 'btn btn-primary pointer']);
        $res .= "</td></tr>";
        return $res;
    }


   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param      $idF
    * @param bool $display
    *
    * @return int|string
    */
    public static function showFieldsDropdown($metademands_id, $selected_value, $idF, $display = true)
    {
        $fields      = new self();
        $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
        $data        = [Dropdown::EMPTY_VALUE];
        foreach ($fields_data as $id => $value) {
            if ($value['item'] != "ITILCategory_Metademands"
             && $value['item'] != "informations"
             && $idF != $id) {
                $data[$id] = $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));
                //            if (!empty($value['label2'])) {
                //               $data[$id] .= ' - ' . $value['label2'];
                //            }
            }
        }

        return Dropdown::showFromArray('fields_link[]', $data, ['value' => $selected_value, 'display' => $display]);
    }

   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param bool $display
    * @param      $idF
    *
    * @return int|string
    */
    public static function showHiddenDropdown($metademands_id, $selected_value, $idF, $display = true)
    {
        $fields      = new self();
        $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
        $data        = [Dropdown::EMPTY_VALUE];
        foreach ($fields_data as $id => $value) {
            if ($value['item'] != "ITILCategory_Metademands"
             //             && $value['item'] != "informations"
             && $idF != $id) {
                $data[$id] = $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));

                //            if (!empty($value['label2'])) {
                //               $data[$id] .= ' - ' . urldecode(html_entity_decode($value['label2']));
                //            }
            }
        }

        return Dropdown::showFromArray('hidden_link[]', $data, ['value' => $selected_value, 'display' => $display]);
    }

   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param bool $display
    * @param      $idF
    *
    * @return int|string
    */
    public static function showHiddenBlockDropdown($selected_value, $idF, $display = true)
    {
        $fields = new self();
        $fields->getFromDB($idF);

        return Dropdown::showNumber('hidden_block[]', ['value'   => $selected_value,
                                                     'display' => $display,
                                                     'used'    => [$fields->getField('rank')],
                                                     'min'     => 1,
                                                     'max'     => self::MAX_FIELDS,
                                                     'toadd'   => [0 => Dropdown::EMPTY_VALUE]]);
    }

   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param bool $display
    * @param      $idF
    *
    * @return int|string
    */
    public static function showChildsBlocksDropdown($metademands_id, $selected_values, $idF, $opt, $display = true)
    {
        $fields = new self();
        $fields = $fields->find(["plugin_metademands_metademands_id" => $metademands_id]);
        $blocks = [];
        foreach ($fields as $f) {
            if (!isset($blocks[$f['rank']])) {
                $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
            }
        }
        ksort($blocks);

        $name = "childs_blocks[" . $opt . "]";
        return Dropdown::showFromArray(
            $name,
            $blocks,
            ['values'   => $selected_values,
            'display'  => $display,
            'width'    => '100%',
            'multiple' => true,
            'entity'   => $_SESSION['glpiactiveentities']]
        );
    }

   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param bool $display
    * @param      $idF
    *
    * @return int|string
    */
    public static function showValidationDropdown($metademands_id, $selected_value, $idF, $display = true)
    {
        $fields = new self();
        $fields->getFromDB($idF);
        $right = '';

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($metademands_id);
        if ($metademand->getField('type') == Ticket::INCIDENT_TYPE) {
            $right = 'validate_incident';
        } elseif ($metademand->getField('type') == Ticket::DEMAND_TYPE) {
            $right = 'validate_request';
        }
        return User::dropdown(['name'    => 'users_id_validate[]',
                             'value'   => $selected_value,
                             'display' => $display,
                             'right'   => $right]);
    }

   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param bool $display
    * @param      $idF
    *
    * @return int|string
    */
    public static function showCheckBoxDropdown($metademands_id, $selected_value, $selected_id, $display = true)
    {
        global $CFG_GLPI;

        $fields          = new self();
        $checkboxes      = $fields->find(['plugin_metademands_metademands_id' => $metademands_id,
                                          'type' => 'checkbox']);
        $dropdown_values = [];
        foreach ($checkboxes as $checkbox) {
            $dropdown_values[$checkbox['id']] = $checkbox['name'];
        }
        $rand   = mt_rand();
        $return = Dropdown::showFromArray('checkbox_id[]', $dropdown_values, ['rand' => $rand,
                                                                              'display' => $display,
                                                                              'display_emptychoice' => true,
                                                                              'value' => $selected_id]);
        $params = ['checkbox_id_val' => '__VALUE__',
                 'metademands_id'  => $metademands_id];
        $return .= Ajax::updateItemOnSelectEvent('dropdown_checkbox_id__' . $rand, "checkbox_value" . $rand, $CFG_GLPI["root_doc"] . PLUGIN_METADEMANDS_DIR_NOFULL .
                                                                                                           "/ajax/checkboxValues.php", $params, ['rand' => $rand, 'display' => $display]);

        $arrayValues    = [];
        $arrayValues[0] = Dropdown::EMPTY_VALUE;
        if (!empty($selected_id)) {
            $fields->getFromDB($selected_id);
            $arrayValues = PluginMetademandsField::_unserialize($fields->getField('custom_values'), true);
        }
        $return .= "<span id='checkbox_value$rand'>\n";
        $elements = $arrayValues ?? [];
        $return .= Dropdown::showFromArray('checkbox_value[]', $elements, ['display' => $display,
                                                                  'display_emptychoice' => false,
                                                                  'value' => $selected_value]);
        $return .= "</span>\n";

        return $return;
    }

   /**
    * View custom values for items or types
    *
    * @param array $values
    * @param array $comment
    * @param array $default
    * @param array $options
    *
    * @return void
    */
    public function getEditValue($values = [], $comment = [], $default = [], $options = [])
    {
        $params['value'] = 0;
        $params['item']  = '';
        $params['type']  = '';

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $allowed_custom_types = self::$allowed_custom_types;
        $allowed_custom_items = self::$allowed_custom_items;

        if (in_array($params['value'], $allowed_custom_types)
          || in_array($params['item'], $allowed_custom_items)) {
            echo "<table width='100%' class='metademands_show_values'>";
            if ($params['value'] != "dropdown_multiple" && $params['item'] != 'User') {
                echo "<tr><th colspan='4'>" . __('Custom values', 'metademands') . "</th></tr>";
            }

            echo "<tr><td>";
            echo '<table width=\'100%\' class="tab_cadre">';
            if ($params["value"] == "dropdown_multiple" && empty($params["item"])) {
                $params["item"] = "other";
            }

            if ($params["value"] == "radio") {
                $params["item"] = "radio";
            }
            if ($params["value"] == "checkbox") {
                $params["item"] = "checkbox";
            }

            switch ($params['item']) {
                case 'other':
                    echo "<tr>";
                    echo "<td>";
                    if (is_array($values) && !empty($values)) {
                        echo "<div id='drag'>";
                        echo "<table class='tab_cadre_fixe'>";
                        foreach ($values as $key => $value) {
                            echo "<tr>";

                            echo '<td class="rowhandler control center">';
                            echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                            echo "<p id='custom_values$key'>";
                            echo __('Value') . " " . $key . " ";
                            $name = "custom_values[$key]";
                            echo Html::input($name, ['value' => $value, 'size' => 50]);
                            echo '</p>';
                            echo '</div>';
                            echo '</td>';

                            echo '<td class="rowhandler control center">';
                            echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                          //                     echo "<p id='default_values$key'>";
                            $display_default = false;
                          //                     if ($params['value'] == 'dropdown_multiple') {
                            $display_default = true;
                          //                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                            $checked = "";
                          //                        if (isset($default[$key])
                          //                            && $default[$key] == 1) {
                          //                           $checked = "checked";
                          //                        }
                          //                        echo "<input type='checkbox' name='default_values[" . $key . "]'  value='1' $checked />";
                            echo "<p id='default_values$key'>";
                            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                            $name  = "default_values[" . $key . "]";
                            $value = ($default[$key] ?? 0);
                            Dropdown::showYesNo($name, $value);
                            echo '</p>';
                          //                     }
                          //                     echo '</p>';
                            echo '</div>';
                            echo '</td>';

                            echo '<td class="rowhandler control center">';
                            echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                            echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                            if (isset($params['id'])) {
                                echo self::showSimpleForm(
                                    $this->getFormURL(),
                                    'delete_field_custom_values',
                                    _x('button', 'Delete permanently'),
                                    ['id'                           => $key,
                                    'plugin_metademands_fields_id' => $params['id'],
                                    ],
                                    'fa-times-circle'
                                );
                            }
                            echo '</div>';
                            echo '</td>';

                            echo "</tr>";
                        }
                        if (isset($params['id'])) {
                            echo Html::hidden('fields_id', ['value' => $params["id"]]);
                        }
                        echo '</table>';
                        echo '</div>';
                        echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');
                        echo '</td>';

                        echo "</tr>";
                        echo "<tr>";
                        echo "<td colspan='4' align='right' id='show_custom_fields'>";
                        self::initCustomValue(max(array_keys($values)), false, $display_default);
                        echo "</td>";
                        echo "</tr>";
                    } else {
                       //                  echo "<tr>";
                       //                  echo "<td>";
                        echo __('Value') . " 1 ";
                        echo Html::input('custom_values[1]', ['size' => 50]);
                        echo "</td>";
                        echo "<td>";
                        $display_default = false;
                       //                  if ($params['value'] == 'dropdown_multiple') {
                        $display_default = true;
                       //                     echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                       //                     echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
                        echo "<p id='default_values1'>";
                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                        $name  = "default_values[1]";
                        $value = 1;
                        Dropdown::showYesNo($name, $value);
                        echo '</p>';
                        echo "</td>";
                       //                  }
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td colspan='2' align='right' id='show_custom_fields'>";
                        self::initCustomValue(1, false, $display_default);
                        echo "</td>";
                        echo "</tr>";
                    }
                    break;
                default:
                    break;
            }

            switch ($params['value']) {
                case 'dropdown_multiple':
                    if ($params["item"] != "other"
                    && !empty($params["item"])
                    && $params["item"] != "User") {
                        $item = new $params['item'];

                        $items = $item->find([], ["name ASC"]);
                        foreach ($items as $key => $v) {
                            echo "<tr>";

                            echo "<td>";
                            echo "<p id='custom_values$key'>";

                            echo $v["name"] . " ";
                            echo '</p>';
                            echo "</td>";

                            echo "<td>";
                         //                     echo "<p id='default_values$key'>";
                         //
                         //
                         //                     echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                         //                     $checked = "";
                         //                     if (isset($default[$key])
                         //                         && $default[$key] == 1) {
                         //                        $checked = "checked";
                         //                     }
                         //                     echo "<input type='checkbox' name='default_values[" . $key . "]'  value='1' $checked />";
                            echo "<p id='default_values$key'>";
                            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                            $name  = "default_values[" . $key . "]";
                            $value = ($default[$key] ?? 0);
                            Dropdown::showYesNo($name, $value);
                            echo '</p>';

                         //                     echo '</p>';
                            echo "</td>";
                            echo "<td>";
                            echo "<p id='present_values$key'>";


                            echo " " . __('Display value in the dropdown', 'metademands') . " ";
                            $checked = "";
                            if (isset($values[$key])
                            && $values[$key] != 0) {
                                $checked = "checked";
                            }
                            echo "<input type='checkbox' name='custom_values[$key]'  value='$key' $checked />";

                            echo '</p>';
                            echo "</td>";

                            echo "</tr>";
                        }
                    }
                    break;
                case 'checkbox':
                case 'radio':
                    echo "<tr>";
                    echo "<td>";
                    if (is_array($values) && !empty($values)) {
                        echo "<div id='drag'>";
                        echo "<table class='tab_cadre_fixe'>";
                        foreach ($values as $key => $value) {
                            echo "<tr>";

                            echo '<td class="rowhandler control center">';
                            echo "<p id='custom_values$key'>";
                            echo __('Value') . " " . $key . " ";
                            $name = "custom_values[$key]";
                            echo Html::input($name, ['value' => $value, 'size' => 30]);
                            echo '</p>';
                            echo "</td>";

                            echo '<td class="rowhandler control center">';
                            echo "<p id='comment_values$key'>";
                            if ($params['value'] == 'checkbox' || $params['value'] == 'radio') {
                                echo " " . __('Comment') . " ";
                                $value_comment = "";
                                if (isset($comment[$key])) {
                                    $value_comment = $comment[$key];
                                }
                                $name = "comment_values[" . $key . "]";
                                echo Html::input($name, ['value' => $value_comment, 'size' => 30]);
                            }
                            echo '</p>';
                            echo "</td>";

                            echo "<td>";
                            echo "<p id='default_values$key'>";
                            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                            $name  = "default_values[" . $key . "]";
                            $value = ($default[$key] ?? 0);
                            Dropdown::showYesNo($name, $value);
                            echo '</p>';
                            echo "</td>";

                            echo '<td class="rowhandler control center">';
                            echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                            echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                            if (isset($params['id'])) {
                                echo self::showSimpleForm(
                                    $this->getFormURL(),
                                    'delete_field_custom_values',
                                    _x('button', 'Delete permanently'),
                                    ['id'                           => $key,
                                    'plugin_metademands_fields_id' => $params['id'],
                                    ],
                                    'fa-times-circle'
                                );
                            }
                            echo '</div>';
                            echo '</td>';

                            echo "</tr>";
                        }
                        if (isset($params['id'])) {
                            echo Html::hidden('fields_id', ['value' => $params["id"]]);
                        }
                        echo '</table>';
                        echo '</div>';
                        echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');

                        echo "<tr>";
                        echo "<td colspan='4' align='right' id='show_custom_fields'>";
                        self::initCustomValue(max(array_keys($values)), true, true);
                        echo "</td>";
                        echo "</tr>";
                    } else {
                        echo __('Value') . " 0 ";
                        echo Html::input('custom_values[0]', ['size' => 30]);
                        echo "</td>";
                        echo "<td>";
                        echo " " . __('Comment') . " ";
                        echo Html::input('comment_values[0]', ['size' => 30]);
                        echo "</td>";
                        echo "<td>";
                       //                  echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                       //                  echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
                        echo "<p id='default_values$key'>";
                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                        $name  = "default_values[" . $key . "]";
                        $value = ($default[$key] ?? 0);
                        Dropdown::showYesNo($name, $value);
                        echo '</p>';
                        echo "</td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td colspan='3' align='right'  id='show_custom_fields'>";
                        self::initCustomValue(0, true, true);
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tr>";
                    break;
                case 'yesno': // Show yes/no default value
                    echo "<tr><td id='show_custom_fields'>";
                    echo _n('Default value', 'Default values', 1, 'metademands') . "&nbsp;";
                    if (isset($params['custom_values'])) {
                        $p['value'] = $params['custom_values'];
                    }
                    $data[1] = __('No');
                    $data[2] = __('Yes');

                    Dropdown::showFromArray("custom_values", $data, $p);
                    echo "</td></tr>";
                    break;
                case 'link': // Show yes/no default value
                    echo "<tr><td id='show_custom_fields'>";
                    $linkType = 0;
                    $linkVal  = '';
                    if (isset($params['custom_values']) && !empty($params['custom_values'])) {
                        $params['custom_values'] = self::_unserialize($params['custom_values']);
                        $linkType                = $params['custom_values'][0] ?? "";
                        $linkVal                 = $params['custom_values'][1] ?? "";
                    }
                    echo '<label>' . __("Link") . '</label>';
                    echo Html::input('custom_values[1]', ['value' => $linkVal, 'size' => 30]);
                    echo "</td>";
                    echo "<td>";

                    echo '<label>' . __("Button Type", "metademands") . '</label>&nbsp;';
                    Dropdown::showFromArray(
                        "custom_values[0]",
                        [
                        'button' => __('button', "metademands"),
                        'link_a' => __('Web link')
                        ],
                        ['value' => $linkType]
                    );
                    echo "<br /><i>" . __("*use field \"Additional label\" for the button title", "metademands") . "</i>";
                    echo "</td></tr>";
                    break;
            }

            echo '</td></tr></table>';
            echo "</td></tr></table>";
        }
    }


    public function reorderArray($targetArray, $indexFrom, $indexTo)
    {
        $targetElement  = $targetArray[$indexFrom];
        $magicIncrement = ($indexTo - $indexFrom) / abs($indexTo - $indexFrom);

        for ($Element = $indexFrom; $Element != $indexTo; $Element += $magicIncrement) {
            $targetArray[$Element] = $targetArray[$Element + $magicIncrement];
        }

        $targetArray[$indexTo] = $targetElement;

        return $targetArray;
    }

   /**
    * @param array $params
    */
    public function reorder(array $params)
    {
        $crit = [
         'id' => $params['field_id'],
        ];

        $itemMove = new self();
        $itemMove->getFromDBByCrit($crit);

        $custom_values = self::_unserialize($itemMove->fields["custom_values"]);

        if (isset($params['old_order']) && isset($params['new_order'])) {
            $old_order = $params['old_order'];
            $new_order = $params['new_order'];

            $old_order = $old_order + 1;
            $new_order = $new_order + 1;

            $new_values = $this->reorderArray($custom_values, $old_order, $new_order);

            $itemMove->update([
                              'id'            => $params['field_id'],
                              'custom_values' => self::_serialize($new_values)
                           ]);
        }
    }

   /**
    * @param      $count
    * @param bool $display_comment
    */
   /**
    * @param      $count
    * @param bool $display_comment
    * @param bool $display_default
    */
    public static function initCustomValue($count, $display_comment = false, $display_default = false)
    {
        Html::requireJs("metademands");
        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(['root_doc' => PLUGIN_METADEMANDS_WEBDIR]) . ");";

        echo Html::hidden('display_comment', ['id' => 'display_comment', 'value' => $display_comment]);
        echo Html::hidden('count_custom_values', ['id' => 'count_custom_values', 'value' => $count]);
        echo Html::hidden('display_default', ['id' => 'display_default', 'value' => $display_default]);

        echo "&nbsp;<i class='fa-2x fas fa-plus-square' style='cursor:pointer' 
            onclick='$script metademandWizard.metademands_add_custom_values(\"show_custom_fields\");' 
            title='" . _sx("button", "Add") . "'/></i>&nbsp;";

       //      echo "&nbsp;<i class='fa-2x fas fa-trash-alt' style='cursor:pointer'
       //            onclick='$script metademandWizard.metademands_delete_custom_values(\"custom_values\");'
       //            title='" . _sx('button', 'Delete permanently') . "'/></i>";
    }

   /**
    * @param $valueId
    * @param $display_comment
    * @param $display_default
    */
    public static function addNewValue($valueId, $display_comment, $display_default)
    {
        echo '<table width=\'100%\' class="tab_cadre">';
        echo "<tr>";

        echo "<td id='show_custom_fields'>";
        echo '<p id=\'custom_values' . $valueId . '\'>';
        echo __('Value') . ' ' . $valueId . ' ';
        $name = "custom_values[$valueId]";
        echo Html::input($name, ['size' => 50]);
        echo "</td>";
        echo '</p>';

        echo "<td id='show_custom_fields'>";
        echo '<p id=\'comment_values' . $valueId . '\'>';
        if ($display_comment) {
            echo " " . __('Comment') . " ";
            $name = "comment_values[$valueId]";
            echo Html::input($name, ['size' => 30]);
        }
        echo '</p>';
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo '<p id=\'default_values' . $valueId . '\'>';
        if ($display_default) {
            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
           //         echo '<input type="checkbox" name="default_values[' . $valueId . ']"  value="1"/>';
            $name  = "default_values[$valueId]";
            $value = 0;
            Dropdown::showYesNo($name, $value);
        }
        echo '</p>';
        echo "</td>";

        echo "</tr>";
        echo '</td></tr></table>';
    }

   /**
    * @param $input
    *
    * @return string
    */
    public static function _serialize($input)
    {
        if ($input != null || $input == []) {
            if (is_array($input)) {
                foreach ($input as &$value) {
                    $value = urlencode(Html::cleanPostForTextArea($value));
                }

                return json_encode($input);
            }
        }
    }

   /**
    * @param $input
    *
    * @return mixed
    */
    public static function _unserialize($input)
    {
        if (!empty($input)) {
            if (!is_array($input)) {
                $input = json_decode($input, true);
            }
            if (is_array($input) && !empty($input)) {
                foreach ($input as &$value) {
                    $value = urldecode($value);
                }
            }
        }

        return $input;
    }

   //   /**
   //
   //    * @param $field
   //    * @param $metademands_id
   //    * @param $selected_value
   //    */
   //   static function showFieldsDropdown($metademands_id, $selected_value, $display = true, $idF) {
   //
   //      $fields      = new self();
   //      $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
   //      $data        = [Dropdown::EMPTY_VALUE];
   //      foreach ($fields_data as $id => $value) {
   //         if ($idF != $id) {
   //            $data[$id] = utf8_decode(urldecode(html_entity_decode($value['label'])));
   ////            if (!empty($value['label2'])) {
   ////               $data[$id] .= ' - ' . $value['label2'];
   ////            }
   //         }
   //      }
   //
   //      return Dropdown::showFromArray('fields_link[]', $data, ['value' => $selected_value, 'display' => $display]);
   //   }


   /**
    * @param $params
    * @param $protocol
    *
    * @return array
    */
//   static function methodListMetademandsfields($params, $protocol) {
//
//      if (isset($params['help'])) {
//         return ['help'           => 'bool,optional',
//                 'metademands_id' => 'bool,mandatory'];
//      }
//
//      if (!Session::getLoginUserID()) {
//         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
//      }
//
//      if (!isset($params['metademands_id'])) {
//         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
//      }
//
//      $field  = new self();
//      $result = $field->find(['plugin_metademands_metademands_id' => $params['metademands_id']]);
//
//      return $result;
//   }

   /**
    * @param $metademands_id
    *
    * @return array
    */
    public function listMetademandsfields($metademands_id)
    {
        $field                 = new self();
        $listMetademandsFields = $field->find(['plugin_metademands_metademands_id' => $metademands_id]);

        return $listMetademandsFields;
    }

   /**
    * @param array $input
    *
    * @return array|bool
    */
   /**
    * @param array $input
    *
    * @return array|bool
    */
    public function prepareInputForAdd($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

       //      $meta = new PluginMetademandsMetademand();

       //      if ($meta->getFromDB($input['plugin_metademands_metademands_id'])
       //          && $meta->fields['is_order'] == 1) {
       //         $input['is_basket'] = 1;
       //      }

        if (isset($input["type"]) && $input["type"] == "checkbox") {
            $input["item"] = "checkbox";
        }
        if (isset($input["type"]) && $input["type"] == "radio") {
            $input["item"] = "radio";
        }

        return $input;
    }

   /**
    * @param array $input
    *
    * @return array|bool
    */
   /**
    * @param array $input
    *
    * @return array|bool
    */
    public function prepareInputForUpdate($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }
        if (isset($input["type"]) && $input["type"] == "checkbox") {
            $input["item"] = "checkbox";
        }
        if (isset($input["type"]) && $input["type"] == "radio") {
            $input["item"] = "radio";
        }

        return $input;
    }

    public function cleanDBonPurge()
    {
        $field = new self();
        $field->deleteByCriteria(['parent_field_id' => $this->getID(),
                                'type'            => 'parent_field']);

        $temp = new PluginMetademandsTicket_Field();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']]);

        $temp = new PluginMetademandsBasketline();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']]);
    }

   /**
    * @param $value
    *
    * @return bool|string
    */
    public static function setColor($value)
    {
        return substr(substr(dechex(($value * 298)), 0, 2) .
                    substr(dechex(($value * 7777)), 0, 3) .
                    substr(dechex(($value * 1)), 0, 1) .
                    substr(dechex(($value * 64)), 0, 1) .
                    substr(dechex(($value * 13)), 0, 1) .
                    substr(dechex(($value * 1)), 0, 1), 0, 6);
    }

   /**
    * @param $input
    *
    * @return bool
    */
    public function checkMandatoryFields($input)
    {
        $msg     = [];
        $checkKo = false;

        $mandatory_fields = ['name'   => __('Label'),
                           'label2' => __('Additional label', 'metademands'),
                           'type'   => __('Type'),
                           'item'   => __('Object', 'metademands')];

        foreach ($input as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    if (($key == 'item' && ($input['type'] == 'dropdown'
                                       || $input['type'] == 'dropdown_object'
                                       || $input['type'] == 'dropdown_meta'))
                    || ($key == 'label2' && ($input['type'] == 'date_interval' || $input['type'] == 'datetime_interval'))) {
                        $msg[]   = $mandatory_fields[$key];
                        $checkKo = true;
                    } elseif ($key != 'item' && $key != 'label2') {
                        $msg[]   = $mandatory_fields[$key];
                        $checkKo = true;
                    }
                }
            }
            $_SESSION['glpi_plugin_metademands_fields'][$key] = $value;
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)), false, ERROR);
            return false;
        }
        return true;
    }

   /**
    * @return array
    */
   /**
    * @return array
    */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(1)
        ];

        $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'itemlink_type' => $this->getType()
        ];

        $tab[] = [
         'id'       => '30',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
        ];

        $tab[] = [
         'id'            => '814',
         'table'         => $this->getTable(),
         'field'         => 'rank',
         'name'          => __('Block', 'metademands'),
         'datatype'      => 'specific',
         'massiveaction' => true
        ];

        $tab[] = [
         'id'            => '815',
         'table'         => $this->getTable(),
         'field'         => 'order',
         'name'          => __('Order', 'metademands'),
         'datatype'      => 'specific',
         'massiveaction' => false
        ];

        $tab[] = [
         'id'       => '817',
         'table'    => $this->getTable(),
         'field'    => 'label2',
         'name'     => __('Additional label', 'metademands'),
         'datatype' => 'text'
        ];

        $tab[] = [
         'id'       => '818',
         'table'    => $this->getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'text'
        ];

        $tab[] = [
         'id'       => '819',
         'table'    => $this->getTable(),
         'field'    => 'is_mandatory',
         'name'     => __('Mandatory field'),
         'datatype' => 'bool'
        ];

        $tab[] = [
         'id'       => '820',
         'table'    => $this->getTable(),
         'field'    => 'is_basket',
         'name'     => __('Display into the basket', 'metademands'),
         'datatype' => 'bool'
        ];

        $tab[] = [
         'id'       => '880',
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'name'     => __('Entity'),
         'datatype' => 'dropdown'
        ];

        $tab[] = [
         'id'       => '886',
         'table'    => $this->getTable(),
         'field'    => 'is_recursive',
         'name'     => __('Child entities'),
         'datatype' => 'bool'
        ];

        return $tab;
    }

   /**
    * @param $field
    * @param $name (default '')
    * @param $values (default '')
    * @param $options   array
    *
    * @return string
    **@since version 0.84
    *
    */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'rank':
                $options['min'] = 1;
                $options['max'] = self::MAX_FIELDS;

                return Dropdown::showNumber($name, $options);
                break;
            case 'order':
                return Dropdown::showNumber($name, $options);
                break;
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

   /**
    * @param $rank
    * @param $fields_id
    * @param $previous_fields_id
    * @param $metademands_id
    */
    public function showOrderDropdown($rank, $fields_id, $previous_fields_id, $metademands_id)
    {
        if (empty($rank)) {
            $rank = 1;
        }
        $restrict = ['rank' => $rank, 'plugin_metademands_metademands_id' => $metademands_id];
       //      $restrict += ['NOT' => ['type' => 'title-block']];
        if (!empty($fields_id)) {
            $restrict += ['NOT' => ['id' => $fields_id]];
        }

        $order = [Dropdown::EMPTY_VALUE];

        foreach ($this->find($restrict, ['order']) as $id => $values) {
            $order[$id] = $values['name'];
            //if (!empty($values['label2'])) {
            //   $order[$id] .= ' - ' . $values['label2'];
            //}
            if (empty(trim($order[$id]))) {
                $order[$id] = __('ID') . " - " . $id;
            }
        }
        Dropdown::showFromArray('plugin_metademands_fields_id', $order, ['value' => $previous_fields_id]);
    }

   /**
    * @param $input
    */
    public function recalculateOrder($input)
    {
        $previousfield = new self();
        $new_order     = [];

        // Set current field after selected field
        if (!empty($input['plugin_metademands_fields_id'])) {
            $previousfield->getFromDB($input['plugin_metademands_fields_id']);
            $input['order'] = $previousfield->fields['order'] + 1;
        } else {
            $input['order'] = 1;
        }

        // Calculate order
        foreach ($this->find(
            ['rank'                              => $input['rank'],
            'plugin_metademands_metademands_id' => $input["plugin_metademands_metademands_id"]],
            ['order']
        ) as $fields_id => $values) {
            if ($fields_id == $input['id']) {
                $values['order'] = $input['order'];
            }
            if ($values['order'] >= $input['order'] && $values['id'] != $input['id']) {
                $new_order[$fields_id] = $values['order'] + 1;
            } else {
                $new_order[$fields_id] = $values['order'];
            }
        }
        asort($new_order);// sort by value

       // Update the new order on each fields of the rank
        $count    = 1;// reinit orders with a counter
        $previous = [];
        foreach ($new_order as $fields_id => $order) {
            $previous[$count] = $fields_id;
            $myfield          = new self();
            $myfield->getFromDB($fields_id);
            // Update order
            $myfield->fields['order'] = $count;
            // Update previous fields_id
            if (isset($previous[$count - 1])) {
                $myfield->fields['plugin_metademands_fields_id'] = $previous[$count - 1];
            } else {
                $myfield->fields['plugin_metademands_fields_id'] = 0;
            }
            $myfield->updateInDB(['order', 'plugin_metademands_fields_id']);
            $count++;
        }
    }


   /**
    * Returns the translation of the field
    *
    * @param type  $item
    * @param type  $field
    *
    * @return type
    * @global type $DB
    *
    */
    public static function displayField($id, $field, $lang = '')
    {
        global $DB;

        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
                                  'FROM'  => 'glpi_plugin_metademands_fieldtranslations',
                                  'WHERE' => [
                                     'itemtype' => self::getType(),
                                     'items_id' => $id,
                                     'field'    => $field,
                                     'language' => $_SESSION['glpilanguage']
                                  ]]);
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                                      'FROM'  => 'glpi_plugin_metademands_fieldtranslations',
                                      'WHERE' => [
                                         'itemtype' => self::getType(),
                                         'items_id' => $id,
                                         'field'    => $field,
                                         'language' => $lang
                                      ]]);
        }


        if (count($iterator)) {
            foreach ($iterator as $data) {
                $res = $data['value'];
            }
        }
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            if (count($iterator2)) {
                foreach ($iterator2 as $data2) {
                    $res .= ' / ' . $data2['value'];
                    $iterator2->next();
                }
            }
        }
        return $res;
    }


   /**
    * @return array
    */
   /**
    * @return array
    */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        $forbidden[] = 'merge';
        $forbidden[] = 'add_transfer_list';
        $forbidden[] = 'amend_comment';

        return $forbidden;
    }

   /**
    * @return array[]
    */
    public static function getGlpiObject()
    {
        $optgroup = [
         __("Assets")         => [
            Computer::class         => Computer::getTypeName(2),
            Monitor::class          => Monitor::getTypeName(2),
            Software::class         => Software::getTypeName(2),
            Networkequipment::class => Networkequipment::getTypeName(2),
            Peripheral::class       => Peripheral::getTypeName(2),
            Printer::class          => Printer::getTypeName(2),
            CartridgeItem::class    => CartridgeItem::getTypeName(2),
            ConsumableItem::class   => ConsumableItem::getTypeName(2),
            Phone::class            => Phone::getTypeName(2),
            Line::class             => Line::getTypeName(2)],
         __("Assistance")     => [
            Ticket::class          => Ticket::getTypeName(2),
            Problem::class         => Problem::getTypeName(2),
            TicketRecurrent::class => TicketRecurrent::getTypeName(2)],
         __("Management")     => [
            Budget::class    => Budget::getTypeName(2),
            Supplier::class  => Supplier::getTypeName(2),
            Contact::class   => Contact::getTypeName(2),
            Contract::class  => Contract::getTypeName(2),
            Document::class  => Document::getTypeName(2),
            Project::class   => Project::getTypeName(2),
            Appliance::class => Appliance::getTypeName(2)],
         __("Tools")          => [
            Reminder::class => __("Notes"),
            RSSFeed::class  => __("RSS feed")],
         __("Administration") => [
            User::class    => User::getTypeName(2),
            Group::class   => Group::getTypeName(2),
            Entity::class  => Entity::getTypeName(2),
            Profile::class => Profile::getTypeName(2)],
        ];
        if (class_exists(PassiveDCEquipment::class)) {
            // Does not exists in GLPI 9.4
            $optgroup[__("Assets")][PassiveDCEquipment::class] = PassiveDCEquipment::getTypeName(2);
        }

        return $optgroup;
    }

   /**
    * Make a select box for Ticket my devices
    *
    * @param integer $userID User ID for my device section (default 0)
    * @param integer $entity_restrict restrict to a specific entity (default -1)
    * @param int     $itemtype of selected item (default 0)
    * @param integer $items_id of selected item (default 0)
    * @param array   $options array of possible options:
    *    - used     : ID of the requester user
    *    - multiple : allow multiple choice
    *
    * @return void
    */
    public static function dropdownMyDevices($userID = 0, $entity_restrict = -1, $itemtype = 0, $items_id = 0, $options = [], $display = true)
    {
        global $DB, $CFG_GLPI;

        $params = ['tickets_id' => 0,
                 'used'       => [],
                 'multiple'   => false,
                 'name'       => 'my_items',
                 'value'      => 0,
                 'rand'       => mt_rand()];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }
       //
       //      if ($userID == 0) {
       //         $userID = Session::getLoginUserID();
       //      }

        $rand        = $params['rand'];
        $already_add = $params['used'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => Dropdown::EMPTY_VALUE];
            $devices    = [];

            // My items
            foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                && Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                    'FROM'  => $itemtable,
                    'WHERE' => [
                                'users_id' => $userID
                             ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                    'ORDER' => $item->getNameField()
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb       = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                                        'SELECT'    => [
                                           'glpi_groups_users.groups_id',
                                           'glpi_groups.name'
                                        ],
                                        'FROM'      => 'glpi_groups_users',
                                        'LEFT JOIN' => [
                                           'glpi_groups' => [
                                              'ON' => [
                                                 'glpi_groups_users' => 'groups_id',
                                                 'glpi_groups'       => 'id'
                                              ]
                                           ]
                                        ],
                                        'WHERE'     => [
                                                          'glpi_groups_users.users_id' => $userID
                                                       ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
                                     ]);

                $devices = [];
                $groups  = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups                     = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups                       = array_merge($groups, $a_groups);
                    }

                    foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                        && Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria  = [
                            'FROM'  => $itemtable,
                            'WHERE' => [
                                      'groups_id' => $groups
                                   ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                            'ORDER' => 'name'
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect($CFG_GLPI['software_types'], $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]);
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                                              'SELECT'    => [
                                                 'glpi_softwareversions.name AS version',
                                                 'glpi_softwares.name AS name',
                                                 'glpi_softwares.id'
                                              ],
                                              'DISTINCT'  => true,
                                              'FROM'      => 'glpi_items_softwareversions',
                                              'LEFT JOIN' => [
                                                 'glpi_softwareversions' => [
                                                    'ON' => [
                                                       'glpi_items_softwareversions' => 'softwareversions_id',
                                                       'glpi_softwareversions'       => 'id'
                                                    ]
                                                 ],
                                                 'glpi_softwares'        => [
                                                    'ON' => [
                                                       'glpi_softwareversions' => 'softwares_id',
                                                       'glpi_softwares'        => 'id'
                                                    ]
                                                 ]
                                              ],
                                              'WHERE'     => [
                                                                'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                                                'glpi_items_softwareversions.itemtype' => $itemtype,
                                                                'glpi_softwares.is_helpdesk_visible'   => 1
                                                             ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                                              'ORDERBY'   => 'glpi_softwares.name'
                                           ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item      = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"]
                                        )
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer']) && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                    && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                        'SELECT'    => "$itemtable.*",
                        'DISTINCT'  => true,
                        'FROM'      => 'glpi_computers_items',
                        'LEFT JOIN' => [
                        $itemtable => [
                           'ON' => [
                              'glpi_computers_items' => 'items_id',
                              $itemtable             => 'id'
                           ]
                        ]
                        ],
                        'WHERE'     => [
                                       'glpi_computers_items.itemtype'     => $itemtype,
                                       'glpi_computers_items.computers_id' => $already_add['Computer']
                                    ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                        'ORDERBY'   => "$itemtable.name"
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }

            $return = "<span id='show_items_id_requester'>";
            $return .= Dropdown::showFromArray($params['name'], $my_devices, ['rand' => $rand, 'display' => false, 'value' => $params['value']]);
            $return .= "</span>";

            if ($display) {
                echo $return;
            } else {
                return $return;
            }
            // Auto update summary of active or just solved tickets
           //         $params = ['my_items' => '__VALUE__'];
           //
           //         Ajax::updateItemOnSelectEvent("dropdown_my_items$rand", "item_ticket_selection_information$rand",
           //                                       $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php",
           //                                       $params);
        }
    }

    public function getProfileJoinCriteria()
    {
        return [
         'INNER JOIN' => [
            Profile_User::getTable() => [
               'ON' => [
                  Profile_User::getTable() => 'users_id',
                  User::getTable()         => 'id'
               ]
            ]
         ],
         'WHERE'      => getEntitiesRestrictCriteria(
             Profile_User::getTable(),
             'entities_id',
             $_SESSION['glpiactiveentities'],
             true
         )
        ];
    }

   /**
    * Get request criteria to select uniques users
    *
    * @return array
    * @since 9.4
    *
    */
    final public function getDistinctUserCriteria()
    {
        return [
         'FIELDS'   => [
            User::getTable() . '.id AS users_id',
            User::getTable() . '.language AS language'
         ],
         'DISTINCT' => true,
        ];
    }

   /**
    * @param $id
    **/
    public static function showAvailableTags($id)
    {
        $self = new self();
        $tags = $self->getTags($id);

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th>" . __('Tag') . "</th>
                <th>" . __('Label') . "</th>
            </tr>";
        echo "<tr>
                  <td>#requester.login#</td>
                  <td>" . __('Requester login', 'metademands') . "</td>
               </tr>";
        echo "<tr>
                  <td>#requester.name#</td>
                  <td>" . __('Requester name', 'metademands') . "</td>
               </tr>";
        echo "<tr>
                  <td>#requester.firstname#</td>
                  <td>" . __('Requester firstname', 'metademands') . "</td>
               </tr>";
        echo "<tr>
                  <td>#requester.email#</td>
                  <td>" . __('Requester email', 'metademands') . "</td>
               </tr>";
        foreach ($tags as $tag => $values) {
            echo "<tr>
                  <td>#" . $tag . "#</td>
                  <td>" . $values . "</td>
               </tr>";
        }
        echo "</table></div>";
    }


   /** Display fields Tags available for the metademand $id
    *
    * @param $id
    **/
    public function getTags($id)
    {
        $fields = $this->find(['plugin_metademands_metademands_id' => $id]);
        $res    = [];
        foreach ($fields as $field) {
            $res[$field['id']] = $field['name'];
            if ($field['type'] == 'dropdown_object' && $field['item'] == User::getType()) {
                $res[$field['id'] . ".login"]     = $field['name'] . " : " . __('Login');
                $res[$field['id'] . ".name"]      = $field['name'] . " : " . __('Name');
                $res[$field['id'] . ".firstname"] = $field['name'] . " : " . __('First name');
                $res[$field['id'] . ".email"]     = $field['name'] . " : " . _n('Email', 'Emails', 1);
            }
        }

        return $res;
    }

    public function post_addItem()
    {
        $pluginField = new PluginMetademandsPluginfields();
        $input       = [];
        if (isset($this->input['plugin_fields_fields_id'])) {
            $input['plugin_fields_fields_id']           = $this->input['plugin_fields_fields_id'];
            $input['plugin_metademands_fields_id']      = $this->fields['id'];
            $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
            $pluginField->add($input);
        }
    }

    public function post_updateItem($history = 1)
    {
        $pluginField = new PluginMetademandsPluginfields();
        if (isset($this->input['plugin_fields_fields_id'])) {
            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->fields['id']])) {
                $input                                 = [];
                $input['plugin_fields_fields_id']      = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['id'];
                $input['id']                           = $pluginField->fields['id'];
                $pluginField->update($input);
            } else {
                $input                                      = [];
                $input['plugin_fields_fields_id']           = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id']      = $this->fields['id'];
                $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
                $pluginField->add($input);
            }
        }
    }

    public static function getJStorersetFields($id)
    {
        return "$('div[bloc-id=\"bloc$id\"]').find(':input').each(function() {
     
                                     switch(this.type) {
                                            case 'password':
                                            case 'text':
                                            case 'textarea':
                                            case 'file':
                                            case 'date':
                                            case 'number':
                                            case 'tel':
                                            case 'email':
                                                jQuery(this).val('');
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                jQuery(this).val('0');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                                this.checked = false;
                                                break;
                                        }
                                        regex = /multiselectfield.*_to/g;
                                        totest = this.id;
                                        found = totest.match(regex);
                                        if(found !== null) {
                                          regex = /multiselectfield[0-9]*/;
                                           found = totest.match(regex);
                                           $('#'+found[0]+'_leftAll').click();
                                        }
                                    });
                                    fixButtonIndicator();";
    }

    public static function getJStorersetFieldsByField($id)
    {
        return "$('div[id-field =\"field$id\"]').find(':input').each(function() {
     
                                     switch(this.type) {
                                            case 'password':
                                            case 'text':
                                            case 'textarea':
                                            case 'file':
                                            case 'date':
                                            case 'number':
                                            case 'tel':
                                            case 'email':
                                                jQuery(this).val('');
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                jQuery(this).val('0');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                                this.checked = false;
                                                break;
                                        }
                                        regex = /multiselectfield.*_to/g;
                                        totest = this.id;
                                        found = totest.match(regex);
                                        if(found !== null) {
                                          regex = /multiselectfield[0-9]*/;
                                           found = totest.match(regex);
                                           $('#'+found[0]+'_leftAll').click();
                                        }
                                    });";
    }
}
