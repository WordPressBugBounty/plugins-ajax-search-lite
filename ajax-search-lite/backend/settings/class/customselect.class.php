<?php
if (!class_exists("wpdreamsCustomSelect")) {
    /**
     * Class wpdreamsCustomSelect
     *
     * A customisable drop down UI element.
     *
     * @package  WPDreams/OptionsFramework/Classes
     * @category Class
     * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
     * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
     * @copyright Copyright (c) 2014, Ernest Marcinko
     */
    class wpdreamsCustomSelect extends wpdreamsType {
		private array $selects = array();
		private string $selected = '';

        function getType() {
            parent::getType();
            $this->processData();
            echo "<div class='wpdreamsCustomSelect'>";
            echo "<label for='wpdreamscustomselect_" . self::$_instancenumber . "'>" . $this->label . "</label>";
            echo "<select isparam=1 class='wpdreamscustomselect' id='wpdreamscustomselect_" . self::$_instancenumber . "' name='" . $this->name . "'>";
            foreach ($this->selects as $sel) {
                if (($sel['value'] . "") == ($this->selected . ""))
                    echo "<option value='" . esc_attr($sel['value']) . "' selected='selected'>" . esc_attr($sel['option']) . "</option>";
                else
                    echo "<option value='" . esc_attr($sel['value']) . "'>" . esc_attr($sel['option']) . "</option>";
            }
            echo "</select>";
            echo "<div class='triggerer'></div>
      </div>";
        }

        function processData() {
            $this->selects = $this->data['selects'];
            $this->selected = $this->data['value'];
        }

        final function getData() {
            return $this->data;
        }

        final function getSelected() {
            return $this->selected;
        }

    }
}