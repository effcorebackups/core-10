<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\tree {
          use \effcore\tree;
          use \effcore\tabs;
          use \effcore\entity;
          use \effcore\message;
          use \effcore\translation;
          abstract class events_module extends \effcore\events_module {

  static function on_start() {
    tree::init();
    foreach(tree::get_item() as $c_item) {
      if ($c_item->id_parent) {
        $c_parent = !empty($c_item->parent_is_tree) ?
            tree::get     ($c_item->id_parent) :
            tree::get_item($c_item->id_parent);
        $c_parent->child_insert($c_item, $c_item->id);
      }
    };
    tabs::init();
    foreach(tabs::get_item() as $c_item) {
      if ($c_item->id_parent) {
        $c_parent = !empty($c_item->parent_is_tab) ?
            tabs::get     ($c_item->id_parent) :
            tabs::get_item($c_item->id_parent);
        $c_parent->child_insert($c_item, $c_item->id);
      }
    };
  }

  static function on_install($module_id = 'tree') {
    return parent::on_install($module_id);
  }

}}