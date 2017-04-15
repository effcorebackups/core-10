<?php

namespace effectivecore {
          class table_head_row_cell extends dom_node {

  public $template = 'table_head_row_cell';

  function add_child($child, $id = null) {
    parent::add_child(
      is_string($child) ? new dom_text($child) : $child, $id
    );
  }

  function render() {
    if (count($this->children)) {
      return (new template($this->template, [
        'attributes' => factory::data_to_attr($this->attributes, ' '),
        'data'       => $this->render_children($this->children),
      ]))->render();
    }
  }

}}