<?php

/*

OutlineForm (Basic Elements)
----------------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

class OutlineFormElement_text extends OutlineFormElement {
  public function render($subelement = null) {
    return '<input type="text" '.$this->build_attr() . ' />';
  }
}

class OutlineFormElement_password extends OutlineFormElement {
  public function render($subelement = null) {
    if ($this->attr['#repeat']) {
      # we should not be modifying the $attr array at this point - we need
      # to work on a copy of the attributes, when using build_attr ...
      $this->attr['name'] = $this->attr['#name'] . ( $subelement == 'repeat' ? '[repeat]' : '[value]' );
    }
    return '<input type="password" '.$this->build_attr() . ' />';
  }
}

class OutlineFormElement_submit extends OutlineFormElement {
  public function render($subelement = null) {
    return '<input type="submit" '.$this->build_attr() . ' />';
  }
}
