<?php

include("ColoredCli.php");
include("ConsoleReader.php");

$reader = new \Odin\Console\ConsoleReader();
while(true){
	$command = $reader->read();
}