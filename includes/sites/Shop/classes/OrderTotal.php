<?php
/*
  osCommerce Online Merchant $osCommerce-SIG$
  Copyright (c) 2010 osCommerce (http://www.oscommerce.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v2 (1991)
  as published by the Free Software Foundation.
*/

  namespace osCommerce\OM\Site\Shop;

  use osCommerce\OM\Registry;

  class OrderTotal {
    protected $_modules = array();
    protected $_data = array();
    protected $_group = 'order_total';

    public function __construct() {
      $OSCOM_Database = Registry::get('Database');
      $OSCOM_Language = Registry::get('Language');

      $Qmodules = $OSCOM_Database->query('select code from :table_templates_boxes where modules_group = "order_total"');
      $Qmodules->setCache('modules-order_total');
      $Qmodules->execute();

      while ( $Qmodules->next() ) {
        $this->_modules[] = $Qmodules->value('code');
      }

      $Qmodules->freeResult();

      $OSCOM_Language->load('modules-order_total');

      foreach ( $this->_modules as $module ) {
        $class_name = 'osCommerce\\OM\\Site\\Shop\\Module\\OrderTotal\\' . $module;

        Registry::set('OrderTotal_' . $module, new $class_name(), true);
      }

      usort($this->_modules, function ($a, $b) {
        if ( Registry::get('OrderTotal_' . $a)->getSortOrder() == Registry::get('OrderTotal_' . $b)->getSortOrder() ) {
          return strnatcasecmp(Registry::get('OrderTotal_' . $a)->getTitle(), Registry::get('OrderTotal_' . $b)->getTitle());
        }

        return (Registry::get('OrderTotal_' . $a)->getSortOrder() < Registry::get('OrderTotal_' . $b)->getSortOrder()) ? -1 : 1;
      });
    }

    public function getCode() {
      return $this->_code;
    }

    public function getTitle() {
      return $this->_title;
    }

    public function getDescription() {
      return $this->_description;
    }

    public function isEnabled() {
      return $this->_status;
    }

    public function getSortOrder() {
      return $this->_sort_order;
    }

    public function getResult() {
      $this->_data = array();

      foreach ( $this->_modules as $module ) {
        $OSCOM_OrderTotal_Module = Registry::get('OrderTotal_' . $module);

        if ( $OSCOM_OrderTotal_Module->isEnabled() ) {
          $OSCOM_OrderTotal_Module->process();

          foreach ( $OSCOM_OrderTotal_Module->output as $output ) {
            if ( !empty($output['title']) && !empty($output['text']) ) {
              $this->_data[] = array('code' => $OSCOM_OrderTotal_Module->getCode(),
                                     'title' => $output['title'],
                                     'text' => $output['text'],
                                     'value' => $output['value'],
                                     'sort_order' => $OSCOM_OrderTotal_Module->getSortOrder());
            }
          }
        }
      }

      return $this->_data;
    }

    public function hasActive() {
      static $has_active;

      if ( !isset($has_active) ) {
        $has_active = false;

        foreach ( $this->_modules as $module ) {
          if ( Registry::get('OrderTotal_' . $module)->isEnabled() ) {
            $has_active = true;
            break;
          }
        }
      }

      return $has_active;
    }
  }
?>
