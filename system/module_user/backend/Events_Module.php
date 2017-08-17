<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore\modules\user {
          use \effectivecore\url;
          use \effectivecore\messages_factory as messages;
          use \effectivecore\modules\user\session_factory as session;
          use \effectivecore\modules\storage\storage_factory as storages;
          abstract class events_module extends \effectivecore\events_module {

  static function on_start() {
    session::init();
  }

  static function on_install() {
    foreach (storages::get('settings')->select('entities')['user'] as $c_entity) $c_entity->install();
    foreach (storages::get('settings')->select('entities_instances')['user'] as $c_instance) $c_instance->insert();
    messages::add_new('Database for module "user" was installed');
  }

}}