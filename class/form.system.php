<?php

/*

OutlineForm (Basic Elements)
----------------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

class OutlineFormElement_text extends OutlineFormElement {
  public function render($fn = 'system') {
    return '<input type="text" '.$this->build_attr() . ' />';
  }
}

class OutlineFormElement_password extends OutlineFormElement {
  public function render($fn = 'system') {
    return '<input type="password" '.$this->build_attr() . ' />';
  }
}

class OutlineFormElement_submit extends OutlineFormElement {
  public function render($fn = 'system') {
    return '<input type="submit" '.$this->build_attr() . ' />';
  }
}

?>