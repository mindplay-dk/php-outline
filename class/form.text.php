<?php

class OutlineForm_text implements IOutlineFormPlugin {
  
  public static function render(OutlineCompiler &$compiler, $arguments) {
    $compiler->output('<input type="text">');
  }
  
}

?>