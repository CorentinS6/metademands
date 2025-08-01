<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

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
 * PluginMetademandsFieldOption Class
 *
 **/
class PluginMetademandsFieldOption extends CommonDBChild
{
    public static $itemtype = 'PluginMetademandsField';
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    public static $rightname = 'plugin_metademands';

    public static $allowed_options_types = [
        'yesno',
        'checkbox',
        'radio',
        'dropdown_multiple',
        'dropdown',
        'dropdown_object',
        'parent_field',
        'text',
        'tel',
        'email',
        'url',
        'textarea',
        'basket',
    ];
    public static $allowed_options_items = ['other', 'ITILCategory_Metademands', 'urgency'];

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Option', 'Options', $nb, 'metademands');
    }


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }


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
     * Get the standard massive actions which are forbidden
     *
     * @return array an array of massive actions
     **@since version 0.84
     *
     * This should be overloaded in Class
     *
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        //        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(1),
        ];

        $tab[] = [
            'id' => '814',
            'table' => $this->getTable(),
            'field' => 'fields_link',
            'name' => __('Make this field mandatory', 'metademands'),
            'datatype' => 'specific',
            'massiveaction' => true,
        ];

        $tab[] = [
            'id' => '815',
            'table' => $this->getTable(),
            'field' => 'hidden_link',
            'name' => __('Display this hidden field', 'metademands'),
            'datatype' => 'specific',
            'massiveaction' => true,
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
            case 'fields_link':
            case 'hidden_link':

                if (isset($_POST['initial_items'])) {

                    $items = reset($_POST['initial_items']);
                    $item = array_key_last($items);
                    $fieldoption = new self();
                    if ($fieldoption->getFromDB($item)) {
                        $fields = new PluginMetademandsField();
                        $fields_data = $fields->find(['plugin_metademands_fields_id' => $fieldoption->fields['plugin_metademands_fields_id']]);
                        $metademands_id = 0;
                        foreach ($fields_data as $id => $value) {
                            $metademands_id = $value['plugin_metademands_metademands_id'];
                        }
                        if ($metademands_id > 0) {
                            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
                            $data = [Dropdown::EMPTY_VALUE];
                            foreach ($fields_data as $id => $value) {
                                if ($value['item'] != "ITILCategory_Metademands"
                                    && $value['item'] != "informations") {
                                    $data[$id] = $value['rank'] . " - " . urldecode(
                                        html_entity_decode(Toolbox::stripslashes_deep($value['name']))
                                    );
                                }
                            }
                            return Dropdown::showFromArray($name, $data, $options);
                        }
                    }
                }
                return "";
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = self::getNumberOfOptionsForItem($item);
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
    }


    /**
     * Return the number of translations for an item
     *
     * @param item
     *
     * @return int number of translations for this item
     */
    public static function getNumberOfOptionsForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            $dbu->getTableForItemType(__CLASS__),
            ["plugin_metademands_fields_id" => $item->getID()]
        );
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     **
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showOptions($item);

        return true;
    }


    /**
     * Display all options of a field
     *
     * @param $item a Dropdown item
     *
     * @return true;
     **/
    public static function showOptions($item)
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        $rand = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        $allowed_options_types = self::$allowed_options_types;
        $allowed_options_items = self::$allowed_options_items;
        $new_fields = [];

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                if (Plugin::isPluginActive($plug)) {
                    $new_fields = PluginMetademandsField::addPluginFieldItems($plug);
                    if (is_array($new_fields) && count($new_fields) > 0) {
                        $allowed_options_types = array_merge($allowed_options_types, $new_fields);
                    }
                }
            }
        }

        if (!in_array($item->fields['type'], $allowed_options_types)
            && !in_array($item->fields['item'], $allowed_options_items)) {
            echo "<div class='alert alert-warning'>" . __(
                'No options are allowed for this field type',
                'metademands'
            ) . "</div>";
            return false;
        }
        $fieldparameter = new PluginMetademandsFieldParameter();
        if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $item->fields['id']])) {
            if ($fieldparameter->fields['link_to_user']) {
                echo "<div class='alert alert-warning'>" . __(
                    "Options aren't available for a field whose value is linked to a user field",
                    'metademands'
                ) . "</div>";
                return false;
            }
        }

        if ($canedit) {
            echo "<div id='viewoption" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addOption" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = [
                'type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id' => -1,
            ];
            Ajax::updateItemJsCode(
                "viewoption" . $item->getType() . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";

            echo "<script type = \"text/javascript\">
                
                function reloadviewOption(value) {
                    
                    $('#viewoption" . $item->getType() . $item->getID() . $rand . "')
                        .load('" . $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php',{
                        type:\"" . __CLASS__ . "\",
                        parenttype:\"" . get_class($item) . "\"," .
                        $item->getForeignKeyField() . ":" . $item->getID() . ",
                        id:value[0],
                        check_value : value[1],
                        plugin_metademands_tasks_id : value[2],
                        fields_link : value[3],
                        hidden_link : value[4],
                        hidden_block : value[5],
                        childs_blocks : value[6],
                        users_id_validate : value[7],
                        checkbox_id : value[8],
                        }
                    );";

            echo       "}</script>";
            echo "<div class='center'>" .
                "<a class='submit btn btn-primary' href='javascript:addOption" .
                $item->getType() . $item->getID() . "$rand();'>" . __('Add a new option', 'metademands') .
                "</a></div><br>";
        }


        //        $field = new PluginMetademandsField();
        //        $field->getFromDB($item->getID());

        $self = new self();

        $options = $self->find(['plugin_metademands_fields_id' => $item->getID()]);
        if (is_array($options) && count($options) > 0) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='11'>" . __("List of options", 'metademands') . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='11'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("ID") . "</th>";
            echo "<th>" . __('Value to check', 'metademands') . "</th>";
            echo "<th>" . __('Launch a task with the field', 'metademands') . "</th>";
            echo "<th>" . __('Make this field mandatory', 'metademands') . "</th>";
            echo "<th>" . __('Display this hidden field', 'metademands') . "</th>";
            echo "<th>" . __('Display this hidden block', 'metademands') . "</th>";
            echo "<th>" . __('Display this hidden block in the same block', 'metademands') . "</th>";
            echo "<th>" . __('Childs blocks', 'metademands') . "</th>";
            echo "<th>" . __('Launch a validation', 'metademands') . "</th>";
            echo "<th>" . __('Bind to the value of this checkbox', 'metademands') . "</th>";
            //            echo "<th>" . __('Hide submit button', 'metademands') . "</th>";
            echo "</tr>";

            //
            foreach ($options as $data) {
                $data['item'] = $item->fields['item'];
                $data['type'] = $item->fields['type'];

                $metademand_custom = new PluginMetademandsFieldCustomvalue();
                $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
                $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

                if (isset($item->fields['type'])
                    && (in_array($item->fields['type'], $allowed_customvalues_types)
                        || in_array($item->fields['item'], $allowed_customvalues_items))
                    && $item->fields['item'] != "urgency"
                    && $item->fields['item'] != "priority"
                    && $item->fields['item'] != "impact") {
                    $custom_values = [];
                    if ($customs = $metademand_custom->find(
                        ["plugin_metademands_fields_id" => $item->getID()],
                        "rank"
                    )) {
                        if (count($customs) > 0) {
                            $custom_values = $customs;
                        }
                    }
                } else {
                    $metademand_params = new PluginMetademandsFieldParameter();
                    $metademand_params->getFromDBByCrit(
                        ["plugin_metademands_fields_id" => $item->getID()]
                    );
                    $custom_values = $metademand_params->fields['custom'];
                }
                $data['custom_values'] = $custom_values;

                $onhover = '';
                if ($canedit) {
                    $onhover = "style='cursor:pointer'
                           onClick=\"viewEditOption" . $item->getType() . $data['id'] . "$rand();\"";
                }
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }

                echo "<td $onhover>";
                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditOption" . $item->getType() . $data['id'] . "$rand() {\n";
                    $params = [
                        'type' => __CLASS__,
                        'parenttype' => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id' => $data["id"],
                    ];
                    Ajax::updateItemJsCode(
                        "viewoption" . $item->getType() . $item->getID() . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo $data['id'];
                echo "</td>";
                echo "<td $onhover>";
                echo self::getValueToCheck($data);
                echo "</td>";

                echo "<td $onhover>";
                $tasks = new PluginMetademandsTask();
                if ($tasks->getFromDB($data['plugin_metademands_tasks_id'])) {
                    if ($tasks->fields['type'] == PluginMetademandsTask::METADEMAND_TYPE) {
                        $metatask = new PluginMetademandsMetademandTask();
                        if ($metatask->getFromDBByCrit(
                            ["plugin_metademands_tasks_id" => $data['plugin_metademands_tasks_id']]
                        )) {
                            echo Dropdown::getDropdownName(
                                'glpi_plugin_metademands_metademands',
                                $metatask->fields['plugin_metademands_metademands_id']
                            );
                        }
                    } else {
                        echo $tasks->getName();
                    }
                }

                echo "</td>";

                echo "<td $onhover>";
                $fields = new PluginMetademandsField();
                $fields_data = $fields->find(['id' => $data['fields_link']]);
                foreach ($fields_data as $id => $value) {
                    echo $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));
                }
                echo "</td>";

                echo "<td $onhover>";
                $fields = new PluginMetademandsField();
                $fields_data = $fields->find(['id' => $data['hidden_link']]);

                foreach ($fields_data as $id => $value) {
                    $name = $id;
                    if (isset($value['name'])) {
                        $name = $value['name'];
                    }

                    echo $value['rank'] . " - " . urldecode(html_entity_decode($name));
                }
                echo "</td>";

                echo "<td $onhover>";
                if ($data['hidden_block'] > 0) {
                    echo $data['hidden_block'];
                }
                echo "</td>";

                echo "<td $onhover>";
                echo Dropdown::getYesNo($data['hidden_block_same_block']);
                echo "</td>";

                echo "<td $onhover>";
                $blocks = json_decode($data["childs_blocks"], true);
                $i = 0;
                if (is_array($blocks)) {
                    $nb = count($blocks);
                    if ($nb > 0) {
                        foreach ($blocks as $block) {
                            if (is_array($block)) {
                                foreach ($block as $block_number) {
                                    $i++;
                                    echo $block_number;
                                    if ($i < $nb) {
                                        echo ", ";
                                    }
                                }
                            }
                        }
                    }
                }

                echo "</td>";

                echo "<td $onhover>";
                echo getUserName($data["users_id_validate"], 0, true);
                echo "</td>";

                echo "<td $onhover>";

                $fields = new PluginMetademandsField();
                if ($fields->getFromDB($data['checkbox_id'])) {
                    echo $fields->getName();

                    $field_custom = new PluginMetademandsFieldCustomvalue();
                    if ($field_custom->getFromDB($data['checkbox_value'])) {
                        echo "<br>";
                        echo $field_custom->getName();
                    }
                }

                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<div class='center first-bloc'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr  class='tab_bg_1'><td class='center'>" . __('No item to display') . "</td></tr>";
            echo "</table>";
            echo "</div>";
        }


        //      $iterator = $DB->request([
        //                                  'FROM'  => getTableForItemType(__CLASS__),
        //                                  'WHERE' => [
        //                                     'itemtype' => $item->getType(),
        //                                     'items_id' => $item->getID(),
        //                                     'field'    => ['<>', 'completename']
        //                                  ],
        //                                  'ORDER' => ['language ASC']
        //                               ]);
        //      if (count($iterator)) {


        return true;
    }

    public function canCreateItem()
    {
        return true;
    }

    /**
     * Display field option form
     *
     * @param int $ID field (default -1)
     * @param     $options   array
     *
     * @return bool
     */
    public function showForm($ID = -1, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }
        if ($ID > 0) {
            $this->check($ID, UPDATE);
        } else {
            $options['plugin_metademands_fields_id'] = $options['parent']->getField('id');
            // Create item
            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);

        $metademand_custom = new PluginMetademandsFieldCustomvalue();
        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        if (isset($item->fields['type'])
            && (in_array($item->fields['type'], $allowed_customvalues_types)
                || (in_array($item->fields['item'], $allowed_customvalues_items) && $item->fields['item'] != 'Appliance'))
            && $item->fields['item'] != "urgency"
            && $item->fields['item'] != "priority"
            && $item->fields['item'] != "impact") {
            $custom_values = [];
            if ($customs = $metademand_custom->find(["plugin_metademands_fields_id" => $item->getID()], "rank")) {
                if (count($customs) > 0) {
                    $custom_values = $customs;
                }
            }
            $metademand_params = new PluginMetademandsFieldParameter();
            $metademand_params->getFromDBByCrit(
                ["plugin_metademands_fields_id" => $item->getID()]
            );
        } else {
            $metademand_params = new PluginMetademandsFieldParameter();
            $metademand_params->getFromDBByCrit(
                ["plugin_metademands_fields_id" => $item->getID()]
            );
            $custom_values = $metademand_params->fields['custom'];
        }

        $params = [
            'item' => $item->fields['item'],
            'type' => $item->fields['type'],
            'plugin_metademands_metademands_id' => $item->fields['plugin_metademands_metademands_id'],
            'plugin_metademands_fields_id' => $item->getID(),
            'plugin_metademands_tasks_id' => $this->fields['plugin_metademands_tasks_id'] ?? 0,
            'fields_link' => $this->fields['fields_link'] ?? 0,
            'hidden_link' => $this->fields['hidden_link'] ?? 0,
            'hidden_block' => $this->fields['hidden_block'] ?? 0,
            'hidden_block_same_block' => $this->fields['hidden_block_same_block'] ?? 0,
            'custom_values' => $custom_values ?? 0,
            'use_richtext' => $metademand_params->fields['use_richtext'] ?? 0,
            'display_type' => ($item->fields['type'] == 'dropdown_multiple') ?? $metademand_params->fields['display_type'] ?? 0,
            'check_value' => $this->fields['check_value'] ?? 0,
            'users_id_validate' => $this->fields['users_id_validate'] ?? 0,
            'checkbox_id' => $this->fields['checkbox_id'] ?? 0,
            'checkbox_value' => $this->fields['checkbox_value'] ?? 0,
        ];


        if ($this->fields['childs_blocks'] != null) {
            $params['childs_blocks'] = json_decode($this->fields['childs_blocks'], true);
        } else {
            $params['childs_blocks'] = [];
        }

        //Hook to get values saves from plugin
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p = [];
                $p["plugin_metademands_fields_id"] = $item->getID();
                $p["plugin_metademands_metademands_id"] = $item->fields["plugin_metademands_metademands_id"];
                $p["nbOpt"] = $this->fields['id'];

                $new_params = self::getPluginParamsOptions($plug, $p);

                if (Plugin::isPluginActive($plug)
                    && is_array($new_params)) {
                    $params = array_merge($params, $new_params);
                }
            }
        }

        echo Html::hidden('plugin_metademands_fields_id', ['value' => $item->getID()]);
        $params['ID'] = $ID;

        if (isset($_POST['check_value'])) {
            $params['check_value'] = $_POST['check_value'];

            if ($ID == -1) {
                if (isset($_POST['plugin_metademands_tasks_id']) && empty($params['plugin_metademands_tasks_id'])) {
                    $params['plugin_metademands_tasks_id'] = $_POST['plugin_metademands_tasks_id'];
                }
                if (isset($_POST['fields_link']) && empty($params['fields_link'])) {
                    $params['fields_link'] = $_POST['fields_link'];
                }
                if (isset($_POST['hidden_link']) && empty($params['hidden_link'])) {
                    $params['hidden_link'] = $_POST['hidden_link'];
                }
                if (isset($_POST['hidden_block']) && empty($params['hidden_block'])) {
                    $params['hidden_block'] = $_POST['hidden_block'];
                }
                if (isset($_POST['childs_blocks']) && empty($params['childs_blocks'])) {
                    $params['childs_blocks'] = json_decode($_POST['childs_blocks']);
                }
                if (isset($_POST['users_id_validate']) && empty($params['users_id_validate'])) {
                    $params['users_id_validate'] = $_POST['users_id_validate'];
                }
                if (isset($_POST['checkbox_id']) && empty($params['checkbox_id'])) {
                    $params['checkbox_id'] = $_POST['checkbox_id'];
                }
            }
        }

        $class = PluginMetademandsField::getClassFromType($params['type']);

        switch ($params['type']) {
            case 'title-block':
            case 'informations':
            case 'number':
            case 'range':
            case 'freetable':
            case 'time':
            case 'date':
            case 'datetime':
            case 'date_interval':
            case 'upload':
            case 'link':
            case 'datetime_interval':
            case 'title':
                break;
            case 'text':
            case 'tel':
            case 'email':
            case 'url':
            case 'textarea':
            case 'dropdown_meta':
            case 'dropdown_object':
            case 'dropdown':
            case 'dropdown_multiple':
            case 'checkbox':
            case 'radio':
            case 'yesno':
            case 'basket':
                $class::getParamsValueToCheck($this, $item, $params);
                break;
            case 'parent_field':
                echo "<tr>";
                echo "<td>";
                echo __('Field');
                echo "</td>";
                echo "<td>";
                self::showValueToCheck($this, $params);

                echo "</td></tr>";
                break;
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        self::getPluginParamsValueToCheck($plug, $this, $item->getID(), $params);
                    }
                }
                break;
        }

        $this->showFormButtons($options);
        return true;
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
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getParamsOptions'])) {
                    return $item->getParamsOptions($params);
                }
            }
        }
    }

    /**
     * Load data options saves from plugins
     *
     * @param $plug
     */
    public static function getPluginParamsValueToCheck($plug, $fieldoption, $id, $params)
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
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getParamsValueToCheck'])) {
                    return $item->getParamsValueToCheck($fieldoption, $id, $params);
                }
            }
        }
    }

    /**
     * Load data options saves from plugins
     *
     * @param $plug
     */
    public static function showPluginParamsValueToCheck($plug, $params)
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
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'showParamsValueToCheck'])) {
                    return $item->showParamsValueToCheck($params);
                }
            }
        }
    }

    /**
     * @param $item
     * @param $params
     * @return void
     * @throws GlpitestSQLError
     */
    public static function showValueToCheck($item, $params)
    {
        $field = new self();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        if ($item->getID() == 0) {
            foreach ($existing_options as $existing_option) {
                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
            }
        }

        $class = PluginMetademandsField::getClassFromType($params['type']);

        switch ($params['type']) {
            case 'title-block':
            case 'informations':
            case 'basket':
            case 'link':
            case 'upload':
            case 'datetime_interval':
            case 'date_interval':
            case 'datetime':
            case 'time':
            case 'date':
            case 'freetable':
            case 'range':
            case 'number':
            case 'title':
                break;
            case 'yesno':
            case 'radio':
            case 'checkbox':
            case 'dropdown_multiple':
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'textarea':
            case 'url':
            case 'email':
            case 'tel':
            case 'text':
                $class::showValueToCheck($item, $params);
                break;
            case 'parent_field':
                //list of fields
                $fields = [];
                $metademand_parent = new PluginMetademandsMetademand();
                // list of parents
                $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask(
                    $params["plugin_metademands_metademands_id"]
                );
                $fieldclass = new PluginMetademandsField();
                foreach ($metademands_parent as $parent_id) {
                    if ($metademand_parent->getFromDB($parent_id)) {
                        $name_metademand = $metademand_parent->getName();

                        $condition = [
                            'plugin_metademands_metademands_id' => $parent_id,
                            ['NOT' => ['type' => ['parent_field', 'upload']]],
                        ];
                        $datas_fields = $fieldclass->find($condition, ['rank', 'order']);
                        //formatting the name to display (Name of metademand - Father's Field Label - type)
                        foreach ($datas_fields as $data_field) {
                            $fields[$data_field['id']] = $name_metademand . " - " . $data_field['name'] . " - " . PluginMetademandsField::getFieldTypesName(
                                $data_field['type']
                            );
                        }
                    }
                }
                Dropdown::showFromArray('parent_field_id', $fields);
                echo Html::hidden('check_value', ['value' => 0]);
                break;
        }
    }

    public static function getValueToCheck($params)
    {
        global $PLUGIN_HOOKS;

        $class = PluginMetademandsField::getClassFromType($params['type']);

        switch ($params['type']) {
            case 'title-block':
            case 'informations':
            case 'range':
            case 'number':
            case 'freetable':
            case 'date':
            case 'time':
            case 'datetime':
            case 'datetime_interval':
            case 'date_interval':
            case 'upload':
            case 'link':
            case 'title':
                break;
            case 'yesno':
            case 'radio':
            case 'checkbox':
            case 'dropdown_multiple':
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'textarea':
            case 'url':
            case 'email':
            case 'tel':
            case 'text':
                $class::showParamsValueToCheck($params);
                break;
            case 'basket':
                PluginMetademandsBasket::showParamsValueToCheck($params);
                break;
            case 'parent_field':
                $field = new PluginMetademandsField();
                if ($field->getFromDB($params['parent_field_id'])) {
                    if (empty(trim($field->fields['name']))) {
                        echo "ID - " . $params['parent_field_id'];
                    } else {
                        echo $field->fields['name'];
                    }
                }
                break;
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        echo self::showPluginParamsValueToCheck($plug, $params);
                    }
                }
                break;
        }
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

    public static function showLinkHtml($id, $params)
    {
        global $PLUGIN_HOOKS, $CFG_GLPI;

        $task = 1;
        $field = 1;
        $hidden = 1;

        if ($params['type'] == "textarea"
        && isset($params['use_richtext'])
            && $params['use_richtext'] == 1) {
            $task = 0;
            $field = 0;
            $hidden = 0;
        }

        $field_id = $params['plugin_metademands_fields_id'];
        $metademands_id = $params["plugin_metademands_metademands_id"];

        $field_class = new PluginMetademandsField();
        $field_class->getFromDB($field_id);
        $fieldoptions = new self();

        $hasdifference = false;
        if (isset($params['ID'])) {
            foreach ($fieldoptions->find(['id' => $params['ID']]) as $option) {
                if (!$hasdifference && $option['check_value'] != $params['check_value']) {
                    $hasdifference = true;
                }
            }
        }

        // Show task link
        if ($task) {
            echo '<tr><td>';
            echo __('Launch a task with the field', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __(
                'If the value selected equals the value to check, the task is created',
                'metademands'
            ) . '</span>';
            echo '</td><td>';
            $tasksusedarray = [];
            foreach ($fieldoptions->find(['plugin_metademands_fields_id' => $params['plugin_metademands_fields_id'], 'check_value' => $params['check_value']]) as $tasksused) {
                if ($tasksused['plugin_metademands_tasks_id'] > 0) {
                    $tasksusedarray[] = $tasksused['plugin_metademands_tasks_id'];
                }
            }
            PluginMetademandsTask::showAllTasksDropdown($metademands_id, $params['plugin_metademands_tasks_id'], true, $tasksusedarray);
            echo "</td></tr>";
        }
        // Show field link
        if ($field) {
            echo "<tr><td>";
            echo __('Make this field mandatory', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __(
                'If the value selected equals the value to check, the field becomes mandatory',
                'metademands'
            ) . '</span>';
            echo "</td>";
            echo "<td>";

            $fields = new PluginMetademandsField();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
            unset($fields_data[$id]);

            $data = [Dropdown::EMPTY_VALUE];
            $fieldslinkusedarray = [];
            foreach ($fieldoptions->find(['plugin_metademands_fields_id' => $params['plugin_metademands_fields_id']]) as $fieldslinkused) {
                if ($fieldslinkused['fields_link'] > 0) {
                    $fieldslinkusedarray[] = $fieldslinkused['fields_link'];
                }
            }
            foreach ($fields_data as $id => $value) {
                if ($value['item'] != "ITILCategory_Metademands"
                    && $value['item'] != "informations" ) {
                    $data[$id] = $value['rank'] . " - " . urldecode(
                        html_entity_decode(Toolbox::stripslashes_deep($value['name']))
                    );
                }
            }

            Dropdown::showFromArray('fields_link', $data, ['value' => $params['fields_link']]);
            echo "</td></tr>";
        }
        if ($hidden) {
            echo "<tr><td>";
            echo __('Display this hidden field', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __(
                'If the value selected equals the value to check, the field becomes visible',
                'metademands'
            ) . '</span>';
            echo "</td>";
            echo "<td>";

            $fields = new PluginMetademandsField();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
            //            unset($fields_data[$id]);
            $data = [Dropdown::EMPTY_VALUE];
            $hiddenlinkusedarray = [];
            foreach ($fieldoptions->find(['plugin_metademands_fields_id' => $params['plugin_metademands_fields_id']]) as $hiddenlinkused) {
                if ($hiddenlinkused['hidden_link'] > 0) {
                    $hiddenlinkusedarray[] = $hiddenlinkused['hidden_link'];
                }
            }
            foreach ($fields_data as $id => $value) {
                if ($value['item'] != "ITILCategory_Metademands") {
                    $data[$id] = $value['rank'] . " - " . urldecode(
                        html_entity_decode(Toolbox::stripslashes_deep($value['name']))
                    );
                }
            }
            Dropdown::showFromArray('hidden_link', $data, ['value' => $params['hidden_link']]);
            echo "</td></tr>";

            $hiddenblockarray = [];
            if ($params['check_value'] != "") {
                foreach ($fieldoptions->find([
                    'plugin_metademands_fields_id' => $params['plugin_metademands_fields_id'],
                    'check_value' => $params['check_value'],
                ]) as $hiddenblock) {
                    if ($hiddenblock['hidden_block'] > 0) {
                        $hiddenblockarray[] = $hiddenblock['hidden_block'];
                    }
                }
            }

            echo "<tr>";
            echo "<td>";
            echo __('Display this hidden block', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __(
                'If the value selected equals the value to check, the block becomes visible',
                'metademands'
            ) . '</span>';
            echo "</td>";
            echo "<td>";

            if (empty($params['hidden_block'])) {
                $params['hidden_block'] = 0;
            }
            $hidden_blocks = [];
            if (!empty($params['hidden_block'])) {
                $field = new PluginMetademandsField();
                $fields = $field->find(['plugin_metademands_metademands_id' => $metademands_id]);
                $hidden_blocks = [];
                foreach ($fields as $field) {
                    $fieldoptions = new self();
                    $fieldscheck = $fieldoptions->find(
                        ['plugin_metademands_fields_id' => $field['id'], 'hidden_block' => $params['hidden_block']]
                    );
                    foreach ($fieldscheck as $fieldschec) {
                        $hidden_blocks[] = $field['id'];
                    }
                }
                if (count($hidden_blocks) > 1) {
                    echo "<span class='alert alert-warning d-flex'>";
                    echo __(
                        'This block is already used by another field. You can have some problems if the save value to check is used',
                        'metademands'
                    );
                    echo "</span>";
                }
            }

            //            Dropdown::showFromArray('hidden_block', $data, ['value' => $params['hidden_link']]);
            $hiddenblockarray[] = $field_class->getField('rank');

            Dropdown::showNumber('hidden_block', [
                'value' => $params['hidden_block'],
                'used' => $hiddenblockarray,
                'min' => 1,
                'max' => PluginMetademandsField::MAX_FIELDS,
                'toadd' => [0 => Dropdown::EMPTY_VALUE],
            ]);

            echo "</td></tr>";

            echo "<tr>";
            echo "<td>";
            echo __('Display this hidden block in the same block', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __(
                'If the value selected equals the value to check, the block becomes visible on the same block',
                'metademands'
            ) . '</span>';
            echo "</td>";
            echo "<td>";

            if (empty($params['hidden_block_same_block'])) {
                $params['hidden_block_same_block'] = 0;
            }
            Dropdown::showYesNo('hidden_block_same_block', $params['hidden_block_same_block']);

            echo "</td></tr>";


            $childsblockarray = [];
            if ($params['check_value'] != "") {
                foreach ($fieldoptions->find([
                    'plugin_metademands_fields_id' => $params['plugin_metademands_fields_id'],
                    'check_value' => $params['plugin_metademands_fields_id'],
                ]) as $childsblock) {
                    if ($childsblock['childs_blocks'] != "[]") {
                        $childsblockarray[] = $childsblock['childs_blocks'];
                    }
                }
            }
            if (($field_class->getField("type") == "checkbox"
                || $field_class->getField("type") == "radio"
                || $field_class->getField("type") == "text"
                || $field_class->getField("type") == "textarea"
                || $field_class->getField("type") == "group"
                || $field_class->getField("type") == "dropdown"
                || $field_class->getField("type") == "dropdown_object"
                || $field_class->getField("type") == "dropdown_meta"
                || $field_class->getField("type") == "yesno") &&
                ($params['check_value'] == "" || count($childsblockarray) == 0 ||
                    (!empty($params['childs_blocks']) && !$hasdifference && isset($params['ID']) && $params['ID'] > 0))
            ) {
                echo "<tr><td>";
                echo __('Childs blocks', 'metademands');
                echo '</br><span class="metademands_wizard_comments">' . __(
                    'If child blocks exist, these blocks are hidden when you deselect the option configured',
                    'metademands'
                ) . '</span>';
                echo "</td>";
                echo "<td>";
                echo self::showChildsBlocksDropdown($metademands_id, $params['hidden_block'], $params['childs_blocks']);
                echo "</td></tr>";
            }

            $uservalidatearray = [];
            if ($params['check_value'] != "") {
                foreach ($fieldoptions->find([
                    'plugin_metademands_fields_id' => $params['plugin_metademands_fields_id'],
                    'check_value' => $params['plugin_metademands_fields_id'],
                ]) as $uservalidate) {
                    if ($uservalidate['users_id_validate'] > 0) {
                        $uservalidatearray[] = $uservalidate['users_id_validate'];
                    }
                }
            }
            if ($params['check_value'] == "" || count($uservalidatearray) == 0 ||
                (!empty($params['users_id_validate']) && !$hasdifference && isset($params['ID']) && $params['ID'] > 0)) {
                if ($field_class->getField("type") == "checkbox"
                || $field_class->getField("type") == "radio"
                    || $field_class->getField("type") == "dropdown_meta"
                || ($field_class->getField("type") == "dropdown_multiple"
                        &&  $field_class->getField("item") == "Group")) {
                    echo "<tr><td>";
                    echo __('Launch a validation', 'metademands');
                    echo '</br><span class="metademands_wizard_comments">' . __(
                        'If the value selected equals the value to check, the validation is sent to the user',
                        'metademands'
                    ) . '</span>';
                    echo "</td>";
                    echo "<td>";
                    $right = '';
                    $metademand = new PluginMetademandsMetademand();
                    $metademand->getFromDB($metademands_id);
                    if ($metademand->getField('type') == Ticket::INCIDENT_TYPE) {
                        $right = 'validate_incident';
                    } elseif ($metademand->getField('type') == Ticket::DEMAND_TYPE) {
                        $right = 'validate_request';
                    }
                    User::dropdown([
                        'name' => 'users_id_validate',
                        'value' => $params['users_id_validate'],
                        'right' => $right,
                    ]);
                    echo "</td></tr>";
                } else {
                    echo Html::hidden('users_id_validate', ['value' => 0]);
                }
            } else {
                echo Html::hidden('users_id_validate', ['value' => 0]);
            }


            $checkboxarray = [];
            if ($params['check_value'] != "") {
                foreach ($fieldoptions->find([
                    'plugin_metademands_fields_id' => $params['plugin_metademands_fields_id'],
                    'check_value' => $params['plugin_metademands_fields_id'],
                ]) as $checkbox) {
                    if ($checkbox['checkbox_id'] > 0) {
                        $checkboxarray[] = $checkbox['checkbox_id'];
                    }
                }
            }
            if ($field_class->getField("type") == "dropdown_multiple"
            &&  $field_class->getField("item") == "Appliance" &&
                ($params['check_value'] == "" || count($checkboxarray) == 0 ||
                    (!empty($params['checkbox_id']) && !$hasdifference && isset($params['ID']) && $params['ID'] > 0))) {
                echo "<tr><td>";
                echo __('Bind to the value of this checkbox', 'metademands');
                echo '</br><span class="metademands_wizard_comments">' . __(
                    'If the selected value is equal to the value to check, the checkbox value is set',
                    'metademands'
                ) . '</span>';
                echo "</td>";
                echo "<td>";
                $fields = new PluginMetademandsField();
                $checkboxes = $fields->find([
                    'plugin_metademands_metademands_id' => $metademands_id,
                    'type' => 'checkbox',
                ]);
                $dropdown_values = [];
                foreach ($checkboxes as $checkbox) {
                    $dropdown_values[$checkbox['id']] = $checkbox['name'];
                }
                $rand = mt_rand();
                $randcheck = Dropdown::showFromArray('checkbox_id', $dropdown_values, [
                    'display_emptychoice' => true,
                    'value' => $params['checkbox_id'],
                ]);
                $paramsajax = [
                    'checkbox_id_val' => '__VALUE__',
                    'metademands_id' => $metademands_id,
                ];

                Ajax::updateItemOnSelectEvent(
                    'dropdown_checkbox_id' . $randcheck,
                    "checkbox_value",
                    $CFG_GLPI["root_doc"] . PLUGIN_METADEMANDS_DIR_NOFULL . "/ajax/checkboxValues.php",
                    $paramsajax
                );

                $arrayValues = [];
                $arrayValues[0] = Dropdown::EMPTY_VALUE;
                if (!empty($params['checkbox_id'])) {
                    $field_custom = new PluginMetademandsFieldCustomvalue();
                    if ($customs = $field_custom->find(
                        ["plugin_metademands_fields_id" => $params['checkbox_id']],
                        "rank"
                    )) {
                        if (count($customs) > 0) {
                            foreach ($customs as $custom) {
                                $arrayValues[$custom['id']] = $custom['name'];
                            }
                        }
                    }
                }
                echo "<span id='checkbox_value'>\n";
                $elements = $arrayValues ?? [];
                Dropdown::showFromArray('checkbox_value', $elements, [
                    'display_emptychoice' => false,
                    'value' => $params['checkbox_value'],
                ]);
                echo "</span>\n";

                echo "</td></tr>";
            }
        }

        //Hook to print new options from plugins
        //        if (isset($PLUGIN_HOOKS['metademands'])) {
        //            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
        //                $p = $params;
        //                $p["plugin_metademands_fields_id"] = $field_id;
        //                $p["plugin_metademands_metademands_id"] = $metademands_id;
        //                $p["hidden"] = $hidden;
        //
        //
        //                $new_res = self::getPluginShowOptions($plug, $p);
        //                if (Plugin::isPluginActive($plug)
        //                    && !empty($new_res)) {
        //                    echo $new_res;
        //                }
        //            }
        //        }
    }


    /**
     * show options fields from plugins
     *
     * @param $plug
     */
    //    public static function getPluginShowOptions($plug, $params)
    //    {
    //        global $PLUGIN_HOOKS;
    //
    //        $dbu = new DbUtils();
    //        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
    //            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];
    //
    //            foreach ($pluginclasses as $pluginclass) {
    //                if (!class_exists($pluginclass)) {
    //                    continue;
    //                }
    //                $form[$pluginclass] = [];
    //                $item = $dbu->getItemForItemtype($pluginclass);
    //                if ($item && is_callable([$item, 'showOptions'])) {
    //                    return $item->showOptions($params);
    //                }
    //            }
    //        }
    //    }


    /**
     * @param      $metademands_id
     * @param      $selected_value
     * @param bool $display
     * @param      $idF
     *
     * @return int|string
     */
    public static function showChildsBlocksDropdown($metademands_id, $hidden_block, $selected_values)
    {
        $fields = new PluginMetademandsField();
        $fields = $fields->find(["plugin_metademands_metademands_id" => $metademands_id]);
        $blocks = [];
        foreach ($fields as $f) {
            if (!isset($blocks[$f['rank']])) {
                $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
            }
        }
        ksort($blocks);

        unset($blocks[$hidden_block]);
        $values = [];
        if (!is_array($selected_values)) {
            $selected_values = [];
        }
        if (is_array($selected_values)) {
            foreach ($selected_values as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $selected_value) {
                        $values[] = $selected_value;
                    }
                } else {
                    $values[] = $v;
                }
            }
        }


        $name = "childs_blocks[]";
        Dropdown::showFromArray(
            $name,
            $blocks,
            [
                'values' => $values,
                'width' => '100%',
                'multiple' => true,
                'entity' => $_SESSION['glpiactiveentities'],
            ]
        );
    }

    public static function taskScript($data)
    {
        global $PLUGIN_HOOKS;

        $class = PluginMetademandsField::getClassFromType($data['type']);

        switch ($data['type']) {
            case 'title-block':
            case 'informations':
            case 'link':
            case 'upload':
            case 'datetime_interval':
            case 'date_interval':
            case 'datetime':
            case 'time':
            case 'date':
            case 'freetable':
            case 'range':
            case 'number':
            case 'title':
                break;
            case 'yesno':
            case 'basket':
            case 'radio':
            case 'tel':
            case 'email':
            case 'url':
            case 'textarea':
            case 'dropdown_meta':
            case 'dropdown_object':
            case 'dropdown':
            case 'dropdown_multiple':
            case 'checkbox':
            case 'text':
                $class::taskScript($data);
                break;
                //            case 'parent_field':
                //                break;
                //            default:
                //                //plugin case
                //                if (isset($PLUGIN_HOOKS['metademands'])) {
                //                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                //                        if (Plugin::isPluginActive($plug)) {
                //                            $case = self::addPluginFieldHiddenLink($plug, $data);
                //                            return $case;
                //                        }
                //                    }
                //                }
                //                break;
        }
    }

    public static function fieldsMandatoryScript($data)
    {
        global $PLUGIN_HOOKS;

        $class = PluginMetademandsField::getClassFromType($data['type']);

        switch ($data['type']) {
            case 'title-block':
            case 'informations':
            case 'number':
            case 'range':
            case 'freetable':
            case 'date':
            case 'time':
            case 'datetime':
            case 'date_interval':
            case 'datetime_interval':
            case 'upload':
            case 'link':
            case 'title':
                break;
            case 'yesno':
            case 'basket':
            case 'radio':
            case 'checkbox':
            case 'dropdown_multiple':
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'textarea':
            case 'url':
            case 'email':
            case 'tel':
            case 'text':
                $class::fieldsMandatoryScript($data);
                break;
            case 'parent_field':
                break;
            default:
                //plugin case
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        if (Plugin::isPluginActive($plug)) {
                            $case = self::addPluginFieldMandatoryLink($plug, $data);
                            return $case;
                        }
                    }
                }
                break;
        }
    }

    public static function fieldsHiddenScript($data)
    {
        global $PLUGIN_HOOKS;

        $class = PluginMetademandsField::getClassFromType($data['type']);

        switch ($data['type']) {
            case 'title-block':
            case 'informations':
            case 'number':
            case 'range':
            case 'freetable':
            case 'date':
            case 'time':
            case 'date_interval':
            case 'datetime_interval':
            case 'datetime':
            case 'upload':
            case 'link':
            case 'title':
                break;
            case 'tel':
            case 'email':
            case 'url':
            case 'textarea':
            case 'dropdown_meta':
            case 'dropdown_object':
            case 'dropdown':
            case 'dropdown_multiple':
            case 'checkbox':
            case 'radio':
            case 'yesno':
            case 'basket':
            case 'text':
                $class::fieldsHiddenScript($data);
                break;
            case 'parent_field':
                break;
            default:
                //plugin case
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        if (Plugin::isPluginActive($plug)) {
                            $case = self::addPluginFieldHiddenLink($plug, $data);
                            return $case;
                        }
                    }
                }
                break;
        }
    }

    public static function blocksHiddenScript($data)
    {
        global $PLUGIN_HOOKS;

        $class = PluginMetademandsField::getClassFromType($data['type']);

        switch ($data['type']) {
            case 'title-block':
            case 'informations':
            case 'number':
            case 'range':
            case 'freetable':
            case 'date':
            case 'date_interval':
            case 'time':
            case 'datetime':
            case 'datetime_interval':
            case 'upload':
            case 'link':
            case 'title':
                break;
            case 'tel':
            case 'email':
            case 'url':
            case 'textarea':
            case 'dropdown_meta':
            case 'dropdown_object':
            case 'dropdown':
            case 'dropdown_multiple':
            case 'checkbox':
            case 'radio':
            case 'yesno':
            case 'basket':
            case 'text':
                $class::blocksHiddenScript($data);
                break;
            case 'parent_field':
                break;
            default:
                //plugin case
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        if (Plugin::isPluginActive($plug)) {
                            $case = self::addPluginBlockHiddenLink($plug, $data);
                            return $case;
                        }
                    }
                }
                break;
        }
        //        }
    }

    public static function checkboxScript($data)
    {
        if (isset($data['options'])) {
            $check_values = $data['options'];

            if (is_array($check_values)) {
                if (count($check_values) > 0) {
                    foreach ($check_values as $idc => $check_value) {
                        if (!empty($data['options'][$idc]['checkbox_id'])
                            && !empty($data['options'][$idc]['checkbox_value'])) {
                            switch ($data['type']) {
                                case 'dropdown_multiple':
                                    PluginMetademandsDropdownmultiple::checkboxScript($data, $idc);
                                    break;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function checkConditions($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($metaid);

        $metaconditionsparams = PluginMetademandsWizard::getConditionsParams($metademands);

        $class = PluginMetademandsField::getClassFromType($data['type']);

        switch ($data['type']) {
            case 'title-block':
            case 'informations':
            case 'range':
            case 'number':
            case 'freetable':
            case 'date':
            case 'time':
            case 'date_interval':
            case 'datetime_interval':
            case 'datetime':
            case 'upload':
            case 'link':
            case 'title':
                break;
            case 'basket':
            case 'yesno':
            case 'radio':
            case 'checkbox':
            case 'dropdown_multiple':
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'textarea':
            case 'url':
            case 'email':
            case 'tel':
            case 'text':
                $class::checkConditions($data, $metaconditionsparams);
                break;
            case 'parent_field':
                break;
            default:
                //plugin case
                break;
        }
    }

    public static function hideAllblockbyDefault($data = [])
    {
        $metaid = $data['plugin_metademands_metademands_id'] ?? 0;
        $check_values = $data['options'] ?? [];
        $id = $data["id"] ?? 0;

        $script = '';
        $hidden_blocks = [];
        $childs = [];
        $childs_blocks = [];

        foreach ($check_values as $idc => $check_value) {
            foreach ($check_value['hidden_block'] as $hidden_block) {
                if ($hidden_block > 0) {
                    $hidden_blocks[] = $hidden_block;
                }
            }

            $childs_blocks[] = json_decode($check_value['childs_blocks'], true);
        }

        if (isset($childs_blocks) && count($childs_blocks) > 0) {
            foreach ($childs_blocks as $k => $childs_block) {
                if (is_array($childs_block)) {
                    foreach ($childs_block as $childs_bloc) {
                        $childs[] = $childs_bloc;
                    }
                }
            }
        }

        //Fonction to drop loaded hidden_block & child_blocks from default hiding if exists in session
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];

            if (!is_array($session_value)
                && isset($check_values[$session_value])) {
                if (($key = array_search($check_values[$session_value]['hidden_block'], $hidden_blocks)) !== false) {
                    unset($hidden_blocks[$key]);
                }
                $session_childs_blocks = [];
                if (isset($check_values[$session_value]['childs_blocks'])) {
                    $session_childs_blocks[] = json_decode($check_values[$session_value]['childs_blocks'], true);
                }
                if (count($session_childs_blocks) > 0) {
                    foreach ($session_childs_blocks as $k => $session_childs_block) {
                        if (is_array($session_childs_block)) {
                            foreach ($session_childs_block as $session_childs) {
                                if (is_array($session_childs)) {
                                    foreach ($session_childs as $session_child) {
                                        foreach ($childs as $k => $child) {
                                            if (($key = array_search($session_child, $child)) !== false) {
                                                unset($childs[$k][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif (is_array($session_value)) {

                foreach ($session_value as $k => $fieldSession) {
                    if (isset($check_values[$fieldSession])) {
                        if (($key = array_search($check_values[$fieldSession]['hidden_block'], $hidden_blocks)) !== false) {
                            unset($hidden_blocks[$key]);
                        }
                        $session_childs_blocks = [];
                        if (isset($check_values[$fieldSession]['childs_blocks'])) {
                            $session_childs_blocks[] = json_decode($check_values[$fieldSession]['childs_blocks'], true);
                        }
                        if (count($session_childs_blocks) > 0) {
                            foreach ($session_childs_blocks as $k => $session_childs_block) {
                                if (is_array($session_childs_block)) {
                                    foreach ($session_childs_block as $session_childs) {
                                        if (is_array($session_childs)) {
                                            foreach ($session_childs as $session_child) {
                                                foreach ($childs as $k => $child) {
                                                    if (($key = array_search($session_child, $child)) !== false) {
                                                        unset($childs[$k][$key]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        $json_hidden_blocks = json_encode($hidden_blocks);
        $json_childs_blocks = json_encode($childs);

        $script .= "var hidden_blocks = {$json_hidden_blocks};
                    var child_blocks = {$json_childs_blocks};
                    var tohideblock = {};";

        //Prepare subblocks
        $script .= "$.each( hidden_blocks, function( key, value ) {
                        tohideblock[value] = true;
                    });
                    $.each( child_blocks, function( key, value ) {
                        tohideblock[value] = true;
                    });
                    $.each(tohideblock, function( key, value ) {
                                if (value == true) {
                                    $('[bloc-id=\"bloc'+key+'\"]').hide();
                                    $('[bloc-id=\"subbloc'+key+'\"]').hide();
                                    $.each(tohideblock, function( key, value ) {
                                        $('div[bloc-id =\"bloc'+key+'\"]').find(':input').each(function() {
                                             switch(this.type) {
                                                case 'checkbox':
                                                case 'radio':
                                                     var checkname = this.name;
                                                     $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                                    break;
                                            }
                                            jQuery(this).removeAttr('required');

                                        });
                                    });
                                }
                            });";

        return $script;
    }


    public static function emptyAllblockbyDefault($check_values)
    {
        $script = '';
        $hidden_blocks = [];
        $childs = [];
        $childs_blocks = [];
        foreach ($check_values as $idc => $check_value) {
            foreach ($check_value['hidden_block'] as $hidden_block) {
                if ($hidden_block > 0) {
                    $hidden_blocks[] = $hidden_block;
                }
            }

            $childs_blocks[] = json_decode($check_value['childs_blocks'], true);
        }

        if (isset($childs_blocks) && count($childs_blocks) > 0) {
            foreach ($childs_blocks as $k => $childs_block) {
                if (is_array($childs_block)) {
                    foreach ($childs_block as $childs_bloc) {
                        $childs[] = $childs_bloc;
                    }
                }
            }
        }

        $json_hidden_blocks = json_encode($hidden_blocks);
        $json_childs_blocks = json_encode($childs);

        $script .= "var hidden_blocks = {$json_hidden_blocks};
                    var child_blocks = {$json_childs_blocks};
                    var tohideblock = {};";
        $script .= "//by default - hide all
                    $.each( hidden_blocks, function( key, value ) {
                        tohideblock[value] = true;
                    });
                    $.each( child_blocks, function( key, value ) {
                        tohideblock[value] = true;
                    });
                    $.each(tohideblock, function( key, value ) {
                                if (value == true) {
                                    $.each(tohideblock, function( key, value ) {
                                        $('div[bloc-id =\"bloc'+key+'\"]').find(':input').each(function() {
                                             switch(this.type) {
                                                case 'password':
                                                case 'text':
                                                case 'textarea':
                                                case 'file':
                                                case 'date':
                                                case 'number':
                                                case 'range':
                                                case 'tel':
                                                case 'email':
                                                case 'url':
                                                    jQuery(this).val('');
                                                    if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                        tinymce.get(this.id).setContent('');
                                                    }
                                                    break;
                                                case 'select-one':
                                                case 'select-multiple':
                                                    //jQuery(this).val('0').trigger('change');
                                                    break;
                                                case 'checkbox':
                                                case 'radio':
                                                     this.checked = false;
                                                     var checkname = this.name;
                                                     $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                            }
                                            jQuery(this).removeAttr('required');
                                            regex = /multiselectfield.*_to/g;
                                            totest = this.id;
                                            found = totest.match(regex);
                                            if(found !== null) {
                                              regex = /multiselectfield[0-9]*/;
                                               found = totest.match(regex);
                                               $('#'+found[0]+'_leftAll').click();
                                            }
                                        });
                                    });
                                }
                            });";

        return $script;
    }

    public static function setMandatoryBlockFields($metaid, $blockid)
    {

        $script = '';

        $use_as_step = 0;
        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($metaid);
        if ($metademands->fields['step_by_step_mode'] == 1) {
            $use_as_step = 1;
        }

        $title = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
        $nextsteptitle =  __(
            'Next',
            'metademands'
        ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";

        if ($blockid > 0) {
            $fields = new PluginMetademandsField();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metaid, 'rank' => $blockid]);
            if (is_array($fields_data) && count($fields_data) > 0) {

                foreach ($fields_data as $data) {
                    $fieldparameter = new PluginMetademandsFieldParameter();
                    if ($fieldparameter->getFromDBByCrit(
                        ['plugin_metademands_fields_id' => $data['id'], 'is_mandatory' => 1]
                    )) {

                        $id = $data['id'];
                        if ($id > 0) {
                            $script .= "$(\"[name='field[$id]']\").attr('required', 'required');";
                            $script .= "$(\"[check='field[$id]']\").attr('required', 'required');";
                            if ($data['type'] == 'upload') {
                                $script .= "document.querySelector(\"[id-field='field$id'] div input\").required = true;";
                            }
                        }
                    }
                }
            }

            if ($use_as_step == 1) {
                $script .= "document.getElementById('nextBtn').innerHTML = '$nextsteptitle'; ";
            }
        }

        return $script;
    }

    public static function resetMandatoryBlockFields($name)
    {
        return "var blocid = sessionStorage.getItem('hiddenbloc$name');
                                     $('div[bloc-id=\"bloc' + blocid + '\"]').find(':input').each(function() {
                                     switch(this.type) {
                                            case 'checkbox':
                                            case 'radio':
                                                var checkname = this.name;
                                                $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                        }
                                        jQuery(this).removeAttr('required');
                                    });
                                    $('div[bloc-id=\"subbloc' + blocid + '\"]').find(':input').each(function() {
                                     switch(this.type) {
                                            case 'checkbox':
                                            case 'radio':
                                                var checkname = this.name;
                                                $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                        }
                                        jQuery(this).removeAttr('required');
                                    });
                                    ";
    }

    public static function setEmptyBlockFields($name)
    {
        return "var blocid = sessionStorage.getItem('hiddenbloc$name');
                                $('div[bloc-id=\"bloc' + blocid + '\"]').find(':input').each(function() {
                                     switch(this.type) {
                                            case 'password':
                                            case 'text':
                                            case 'textarea':
                                            case 'file':
                                            case 'date':
                                            case 'number':
                                            case 'range':
                                            case 'tel':
                                            case 'email':
                                            case 'url':
                                                jQuery(this).val('');
                                                if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                    tinymce.get(this.id).setContent('');
                                                }
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                                 this.checked = false;
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
                                    $('div[bloc-id=\"subbloc' + blocid + '\"]').find(':input').each(function() {
                                     switch(this.type) {
                                            case 'password':
                                            case 'text':
                                            case 'textarea':
                                            case 'file':
                                            case 'date':
                                            case 'number':
                                            case 'range':
                                            case 'tel':
                                            case 'email':
                                            case 'url':
                                                jQuery(this).val('');
                                                if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                    tinymce.get(this.id).setContent('');
                                                }
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                                 this.checked = false;
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
                            ";
    }


    public static function setMandatoryFieldsByField($field_id, $hidden_link)
    {
        //cannot be used for multples values like checkbox or radio
        $script = '';
        $fieldoptions = new PluginMetademandsFieldOption();
        $fields_data = $fieldoptions->find(
            ['plugin_metademands_fields_id' => $field_id, 'hidden_link' => $hidden_link]
        );

        if (is_array($fields_data) && count($fields_data) > 0) {
            foreach ($fields_data as $data) {
                if ($data['fields_link'] == $hidden_link && $hidden_link > 0) {
                    //                    $script .= "$(\"[name='field[$hidden_link]']\").attr('required', 'required');";
                }
                $field =  new PluginMetademandsField();
                if ($field->getFromDB($hidden_link) && $field->fields['type'] == 'upload') {

                    $script .= "
                    var div = document.getElementById('fileupload_info_ticketfield$hidden_link');
                    if (!div) return;
                    var nextElem = div.nextElementSibling;
                    while (nextElem && nextElem.tagName !== 'INPUT') {
                        nextElem = nextElem.nextElementSibling;
                    }
                     if (nextElem) {
                        nextElem.setAttribute('required', 'required');
                    }";
                }

            }
        }
        return $script;
    }


    public static function resetMandatoryFieldsByField($name)
    {

        return "var fieldid = sessionStorage.getItem('hiddenlink$name');
                            $('div[id-field=\"field' + fieldid + '\"]').find(':input').each(function() {
                                     switch(this.type) {
                                            case 'password':
                                            case 'text':
                                            case 'textarea':
                                            case 'file':
                                            case 'date':
                                            case 'number':
                                            case 'range':
                                            case 'tel':
                                            case 'email':
                                            case 'url':
                                                jQuery(this).val('');
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                            if(this.checked == true) {
                                                this.click();
                                                this.checked = false;
                                                break;
                                            }   
                                        }
                                        jQuery(this).removeAttr('required');
                                        jQuery(this).removeClass('invalid');
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

    public static function checkMandatoryFile($fields_link, $name)
    {
        $field = new PluginMetademandsField();
        if ($field->getFromDB($fields_link)) {
            if ($field->fields['type'] == 'file'
            || $field->fields['type'] == 'checkbox') {
                return "
                var field = sessionStorage.getItem('mandatoryfile$name');
                var fieldid = 'field'+ field;

                if (document.querySelector('[id-field=\"' + fieldid + '\"] div input')){
                    document.querySelector('[id-field=\"' + fieldid + '\"] div input').required = true;
                }
                ";
            }
        }
    }
    /**
     * check fields_link to be mandatory
     * @param $id
     * @param $value
     * @param $fields
     * @return array
     */
    public static function getMandatoryFields($id, $values, $fields_links, $fields)
    {
        $toBeMandatory = [];

        $ids = [];
        if (isset($values['id'])) {
            $ids[] = $values['id'];
        }


        if (in_array($id, $ids) && !array_key_exists($id, $fields)) {
            $toBeMandatory[] = $id;
        }

        if (array_key_exists($id, $fields)
            && in_array($id, $fields_links)
            && $fields[$id] == null
        ) {
            $toBeMandatory[] = $id;
        }

        return $toBeMandatory;
    }


    /**
     * Unset values in data & post for hiddens fields
     *
     * @param $data
     * @param $post
     */
    public static function unsetHidden(&$data, &$post)
    {
        foreach ($data as $id => $value) {
            //if field is hidden remove it from Data & Post
            if (isset($value['options'])) {
                $check_values = $value['options'];

                if (is_array($check_values)) {
                    foreach ($check_values as $idc => $check_value) {
                        $hidden_link = $check_value['hidden_link'];
                        $hidden_block = $check_value['hidden_block'];
                        //                        $taskChild = $check_value['plugin_metademands_tasks_id'];
                        $toKeep = [];
                        //for hidden fields
                        if (!isset($toKeep[$hidden_link])) {
                            $toKeep[$hidden_link] = false;
                        }
                        if (isset($post[$id]) && isset($hidden_link)) {
                            $test = PluginMetademandsTicket_Field::isCheckValueOKFieldsLinks(
                                $post[$id],
                                $idc,
                                $value['type']
                            );
                        } else {
                            $test = false;
                        }

                        if ($test == true) {
                            $toKeep[$hidden_link] = true;
                            //                            if ($taskChild != 0) {
                            //                                $metaTask = new PluginMetademandsMetademandTask();
                            //                                $metaTask->getFromDB($taskChild);
                            //                                $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                            //                                unset($_SESSION['metademands_hide'][$idChild]);
                            //                            }
                        } else {
                            //                            if ($taskChild != 0) {
                            //                                $metaTask = new PluginMetademandsMetademandTask();
                            //                                $metaTask->getFromDB($taskChild);
                            //                                $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                            //                                $_SESSION['metademands_hide'][$idChild] = $idChild;
                            //                            }
                        }
                        $hidden_blocks = [$hidden_block];
                        //include child blocks
                        if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                            $childs_blocks = json_decode($check_value['childs_blocks'], true);
                            if (isset($childs_blocks)
                                && is_array($childs_blocks)
                                && count($childs_blocks) > 0) {
                                foreach ($childs_blocks as $childs) {
                                    if (is_array($childs)) {
                                        foreach ($childs as $childs_block) {
                                            if (!is_array($childs_block)) {
                                                $hidden_blocks[] = $childs_block;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        //for hidden blocks
                        $metademandsFields = new PluginMetademandsField();
                        $metademandsFields = $metademandsFields->find([
                            "rank" => $hidden_blocks,
                            'plugin_metademands_metademands_id' => $value['plugin_metademands_metademands_id'],
                        ], 'order');

                        foreach ($metademandsFields as $metademandField) {
                            if (!isset($toKeep[$metademandField['id']])) {
                                $toKeep[$metademandField['id']] = false;
                            }
                            if (isset($post[$id]) && isset($metademandField['id'])) {
                                $test = PluginMetademandsTicket_Field::isCheckValueOKFieldsLinks(
                                    $post[$id],
                                    $idc,
                                    $value['type']
                                );
                            } else {
                                $test = false;
                            }

                            if ($test == true) {
                                $toKeep[$metademandField['id']] = true;
                                //                                if ($taskChild != 0) {
                                //                                    $metaTask = new PluginMetademandsMetademandTask();
                                //                                    $metaTask->getFromDB($taskChild);
                                //                                    $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                                //                                    unset($_SESSION['metademands_hide'][$idChild]);
                                //                                }
                            } else {
                                //                                if ($taskChild != 0) {
                                //                                    $metaTask = new PluginMetademandsMetademandTask();
                                //                                    $metaTask->getFromDB($taskChild);
                                //                                    $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                                //                                    $_SESSION['metademands_hide'][$idChild] = $idChild;
                                //                                }
                            }
                        }

                        foreach ($toKeep as $k => $v) {
                            if ($v == false) {
                                if (isset($post[$k])) {
                                    unset($post[$k]);
                                }
                                if (isset($data[$k])) {
                                    $data[$k]['is_mandatory'] = false;
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginFieldMandatoryLink($plug, $data)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $check_values = $data['options'] ?? [];
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addFieldMandatoryLink'])) {
                    return $item->addFieldMandatoryLink($data, $check_values);
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginFieldHiddenLink($plug, $data)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $check_values = $data['options'] ?? [];
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addFieldHiddenLink'])) {
                    return $item->addFieldHiddenLink($data, $check_values);
                }
            }
        }
    }

    public static function addPluginBlockHiddenLink($plug, $data)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $check_values = $data['options'] ?? [];
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addBlockHiddenLink'])) {
                    return $item->addBlockHiddenLink($data, $check_values);
                }
            }
        }
    }
}
