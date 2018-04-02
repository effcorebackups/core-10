<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class tabs_item extends \effcore\tree_item {

  public $id;
  public $id_parent;
  public $parent_is_tab;
  public $action_name;
  public $title = '';
  public $template = 'tabs_item';
  public $template_children = null;

  function render() {
    return (new template($this->template, [
      'self'     => $this->render_self(),
      'children' => $this->render_children($this->children)
    ]))->render();
  }

  function render_self() {
    $href = page::get_current()->args_get('base').'/'.$this->action_name;
    $this->attribute_insert('href', $href);
    return parent::render_self();
  }

}}