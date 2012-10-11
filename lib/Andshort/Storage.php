<?php
require_once('Foreachable.php');

abstract class Instropy_Storage extends Foreachable {

    abstract public function __construct($key_storage=NULL);
}
?>
