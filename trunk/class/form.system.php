<?php

/*

OutlineForm (Basic Elements)
----------------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

class OutlineFormElement_text extends OutlineFormElement {
  
  public function setValue($value) {
    $this->attr['value'] = $value;
  }
  
}

?>