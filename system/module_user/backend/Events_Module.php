<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\user {
          use \effcore\user;
          use \effcore\entity;
          use \effcore\session;
          use \effcore\factory;
          use \effcore\message;
          use \effcore\instance;
          use \effcore\translation;
          abstract class events_module extends \effcore\events_module {

  static function on_start() {
    $session = session::select();
    if ($session &&
        $session->id_user) {
      user::init($session->id_user);
    }
  }

  static function on_install($module_id = 'user') {
    return parent::on_install($module_id);
  }

}}