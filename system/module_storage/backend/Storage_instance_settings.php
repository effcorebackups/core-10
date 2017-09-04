<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore {
          use \effectivecore\files_factory as files;
          use \effectivecore\console_factory as console;
          use \effectivecore\messages_factory as messages;
          const settings_cache_file_name      = 'cache--settings.php';
          const settings_cache_file_name_orig = 'cache--settings--original.php';
          const changes_file_name             = 'changes.php';
          class storage_instance_settings {

  static $data_orig;
  static $data;
  static $changes_dynamic;

  static function init() {
    $f_settings = new file(dir_dynamic.settings_cache_file_name);
    if ($f_settings->is_exist()) $f_settings->insert();
    else static::settings_rebuild();
    factory::$state = state_1;
    console::add_log('state', 'set', state_1, '-');
  }

  ########################
  ### shared functions ###
  ########################

  function select($group = '') {
    if (!static::$data) static::init();
    if ($group)  return static::$data[$group];
    else         return static::$data;
  }

  function changes_register_action($module_id, $action, $npath, $value = null, $rebuild = true) {
    $f_settings      = new file(dir_dynamic.settings_cache_file_name);
    $f_settings_orig = new file(dir_dynamic.settings_cache_file_name_orig);
    $f_changes       = new file(dir_dynamic.changes_file_name);
    if ($f_changes->is_exist()) $f_changes->insert();
  # init changes
    $settings_d = isset(static::$changes_dynamic['changes']) ?
                        static::$changes_dynamic['changes'] : [];
  # add new action
    $settings_d[$module_id]->{$action}[$npath] = $value;
  # save data
    if (!is_writable(dir_dynamic) ||
        ($f_settings_orig->is_exist() &&
        !$f_settings_orig->is_writable()) ||
        ($f_settings->is_exist() &&
        !$f_settings->is_writable()) ||
        ($f_changes->is_exist() &&
        !$f_changes->is_writable())) {
      messages::add_new(
        'Can not save file "'.changes_file_name.            '" to the directory "dynamic"!'.br.
        'Check if file "'.    changes_file_name.            '" is writable.'.br.
        'Check if file "'.    settings_cache_file_name.     '" is writable.'.br.
        'Check if file "'.    settings_cache_file_name_orig.'" is writable.'.br.
        'Check if directory "dynamic" is writable.'.br.
        'Setting is not saved.', 'error'
      );
    } else {
      static::$changes_dynamic['changes'] = $settings_d; # prevent opcache work
      static::settings_save_to_file($settings_d, changes_file_name, '  settings::$changes_dynamic[\'changes\']');
      if ($rebuild) {
        static::$data_orig = ['_changed' => date(format_datetime, time())];
        static::settings_rebuild();
      }
    }
  }

  ################
  ### settings ###
  ################

  static function settings_rebuild() {
    $f_settings      = new file(dir_dynamic.settings_cache_file_name);
    $f_settings_orig = new file(dir_dynamic.settings_cache_file_name_orig);
    $f_changes       = new file(dir_dynamic.changes_file_name);
    if ($f_changes->is_exist())       $f_changes->insert();
    if ($f_settings_orig->is_exist()) $f_settings_orig->insert();
  # init original settings
    if (empty(static::$data_orig)) {
      static::$data_orig = ['_created' => date(format_datetime, time())];
      static::$data_orig += static::settings_find_static();
    }
  # init changes
    $settings_d = isset(static::$changes_dynamic['changes']) ?
                        static::$changes_dynamic['changes'] : [];
    $settings_s = isset(static::$data_orig['changes']) ?
                        static::$data_orig['changes'] : [];
  # apply all changes to original settings and get final settings
    $data_new = unserialize(serialize(static::$data_orig)); # deep array clone
    static::changes_apply_to_settings($settings_d, $data_new);
    static::changes_apply_to_settings($settings_s, $data_new);
    static::$data = $data_new; # prevent opcache work
    unset(static::$data['changes']);
  # save cache
    if (!is_writable(dir_dynamic) ||
        ($f_settings_orig->is_exist() &&
        !$f_settings_orig->is_writable()) ||
        ($f_settings->is_exist() &&
        !$f_settings->is_writable())) {
      messages::add_new(
        'Can not save file "'.settings_cache_file_name.     '" to the directory "dynamic"!'.br.
        'Can not save file "'.settings_cache_file_name_orig.'" to the directory "dynamic"!'.br.
        'Check if file "'.    settings_cache_file_name.     '" is writable.'.br.
        'Check if file "'.    settings_cache_file_name_orig.'" is writable.'.br.
        'Check if directory "dynamic" is writable.'.br.
        'System is working slowly at now.', 'warning'
      );
    } else {
      static::settings_save_to_file(static::$data_orig, settings_cache_file_name_orig, '  settings::$data_orig');
      static::settings_save_to_file(static::$data,      settings_cache_file_name,      '  settings::$data');
    }
  }

  static function settings_save_to_file($data, $file_name, $prefix) {
    $file = new file(dir_dynamic.$file_name);
    $file->set_data(
      "<?php\n\nnamespace effectivecore { # ARRAY[type][scope]...\n\n  ".
        "use \\effectivecore\\storage_instance_settings as settings;\n\n".
          factory::data_export($data, $prefix).
      "\n}");
    $file->save();
    if (function_exists('opcache_invalidate')) {
      opcache_invalidate($file->get_path_full());
    }
  }

  static function settings_find_static() {
    $return = [];
    $files = files::get_all(dir_system, '%^.*\.data$%') +
             files::get_all(dir_modules, '%^.*\.data$%');
    $modules_path = [];
    foreach ($files as $c_file) {
      if ($c_file->get_file_full() == 'module.data') {
        $modules_path[$c_file->get_dir_parent()] = $c_file->get_dirs_relative();
      }
    }
    foreach ($files as $c_file) {
      $c_scope = 'global';
      foreach ($modules_path as $c_dir_parent => $c_dir_relative) {
        if (strpos($c_file->get_dirs_relative(), $c_dir_relative) === 0) {
          $c_scope = $c_dir_parent;
          break;
        }
      }
      $c_parsed = static::settings_to_code($c_file->load(), $c_file->get_path_relative());
      foreach ($c_parsed as $c_type => $c_data) {
        if (is_object($c_data)) {
          if ($c_type == 'module') $c_data->path = $modules_path[$c_scope];
          $return[$c_type][$c_scope] = $c_data;
        }
        if (is_array($c_data)) {
          if (!isset($return[$c_type][$c_scope])) $return[$c_type][$c_scope] = [];
          $return[$c_type][$c_scope] += $c_data;
        }
      }
    }
    return $return;
  }

  ###############
  ### changes ###
  ###############

  static function changes_apply_to_settings($changes, &$data) {
    foreach ($changes as $module_id => $c_module_changes) {
      foreach ($c_module_changes as $c_action_id => $c_changes) {
        foreach ($c_changes as $c_npath => $c_value) {
          $path_parts = explode('/', $c_npath);
          $child_name = array_pop($path_parts);
          $parent_obj = &factory::npath_get_pointer(implode('/', $path_parts), $data, true);
          switch ($c_action_id) {
            case 'insert': # only structured types support (array|object)
              switch (gettype($parent_obj)) {
                case 'array' : $destination_obj = &$parent_obj[$child_name];   break;
                case 'object': $destination_obj = &$parent_obj->{$child_name}; break;
              }
              switch (gettype($destination_obj)) {
                case 'array' : foreach ($c_value as $key => $value) $destination_obj[$key]   = $value; break;
                case 'object': foreach ($c_value as $key => $value) $destination_obj->{$key} = $value; break;
              }
              break;
            case 'update': # only scalar types support (string|numeric) @todo: test bool|null
              switch (gettype($parent_obj)) {
                case 'array' : $parent_obj[$child_name]   = $c_value; break;
                case 'object': $parent_obj->{$child_name} = $c_value; break;
              }
              break;
            case 'delete':
              switch (gettype($parent_obj)) {
                case 'array' : unset($parent_obj[$child_name]);   break;
                case 'object': unset($parent_obj->{$child_name}); break;
              }
              break;
            }
        }
      }
    }
  }

  ##############
  ### parser ###
  ##############

  static function code_to_settings($data, $entity_name = '', $entity_prefix = '  ', $depth = 0) {
    $return = [];
    if ($entity_name) {
      $return[] = str_repeat('  ', $depth-1).($depth ? $entity_prefix : '').$entity_name;
    }
    foreach ($data as $key => $value) {
      if (is_array($value)  && !count($value))           continue;
      if (is_object($value) && !get_object_vars($value)) continue;
      if (is_array($value))       $return[] = static::code_to_settings($value, $key, is_array($data) ? '- ' : '  ', $depth + 1);
      else if (is_object($value)) $return[] = static::code_to_settings($value, $key, is_array($data) ? '- ' : '  ', $depth + 1);
      else if ($value === null)   $return[] = str_repeat('  ', $depth).(is_array($data) ? '- ' : '  ').$key.': null';
      else if ($value === false)  $return[] = str_repeat('  ', $depth).(is_array($data) ? '- ' : '  ').$key.': false';
      else if ($value === true)   $return[] = str_repeat('  ', $depth).(is_array($data) ? '- ' : '  ').$key.': true';
      else                        $return[] = str_repeat('  ', $depth).(is_array($data) ? '- ' : '  ').$key.': '.$value;
    }
    return implode(nl, $return);
  }

  static function settings_to_code($data, $file_name = '') {
    $return = new \stdClass();
    $p = [-1 => &$return];
    $pc_objects = [];
    $pi_objects = [];
    $line_num = 0;
    foreach (explode(nl, $data) as $c_line) {
      $line_num++;
    # skip comments
      if (substr(ltrim($c_line, ' '), 0, 1) === '#') continue;
    # ───────────────────
    # available variants:
    # ───────────────────
    # - name
    #   name
    # - name|class_name
    #   name|class_name
    # - name: value
    #   name: value
    # ───────────────────
      $matches = [];
      preg_match('%(?<indent>[ ]*)'.
                  '(?<prefix>- |)'.
                  '(?<name>[^:|]+)'.
                  '(?<class>\\|[a-z0-9_\\\\]+|)'.
                  '(?<delimiter>: |)'.
                  '(?<value>.*)%sS', $c_line, $matches);
      if (!empty($matches['name'])) {
        $c_depth = intval(strlen($matches['indent'].$matches['prefix']) / 2);
      # define current value
        if ($matches['delimiter'] == ': ') {
          $c_value = $matches['value'];
          if (is_numeric($c_value)) $c_value += 0;
          if ($c_value === 'true')  $c_value = true;
          if ($c_value === 'false') $c_value = false;
          if ($c_value === 'null')  $c_value = null;
        } else {
          $c_class_name = !empty($matches['class']) ? '\\effectivecore\\'.substr($matches['class'], 1) : 'stdClass';
          $c_reflection = new \ReflectionClass($c_class_name);
          $c_is_post_constructor = $c_reflection->implementsInterface('\\effectivecore\\post_constructor');
          $c_is_post_init        = $c_reflection->implementsInterface('\\effectivecore\\post_init');
          if ($c_is_post_constructor) $c_value = factory::class_get_new_instance($c_class_name);
          else                        $c_value = new $c_class_name;
          if ($c_is_post_constructor) $pc_objects[] = $c_value;
          if ($c_is_post_init)        $pi_objects[] = $c_value;
        }
      # add new item to tree
        if (is_array($p[$c_depth-1])) {
          $p[$c_depth-1][$matches['name']] = $c_value;
          $p[$c_depth] = &$p[$c_depth-1][$matches['name']];
        } else {
          $p[$c_depth-1]->{$matches['name']} = $c_value;
          $p[$c_depth] = &$p[$c_depth-1]->{$matches['name']};
        }
      # convert parent item to array
        if ($matches['prefix'] == '- ' && !is_array($p[$c_depth-1])) {
          $p[$c_depth-1] = (array)$p[$c_depth-1];
        }
      } else {
        $messages = ['Function: settings_to_code', 'Wrong syntax in settings data at line: '.$line_num];
        if ($file_name) $messages[] = 'File name: '.$file_name;
        messages::add_new(implode(br, $messages), 'error');
      }
    }
  # call required functions
    foreach ($pc_objects as $c_object) $c_object->__construct();
    foreach ($pi_objects as $c_object) $c_object->init();
    return $return;
  }

}}