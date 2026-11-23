<?php


use Controllers\RootController;
use ControllerStack\ControllerStack;
use NamedControllers\AdminController;

require "../vendor/autoload.php";
require "vendor/autoload.php";

session_start();

/*
 * The idea behind the version 2 is that we give a folder, this folder will contain a root 'class'
 *
 * the root class will contain all the methods that are available on root
 * i.e.,
 * 		example.com/ -> points to the root method
 * 		example.com/Example -> points to a method in the root class OR the root method in subfolder Example NOT BOTH
 *
 *
 * During discovery we must check that if there are subfolders in root that these names do not collide with methods in the root class
 * During discovery we must also check that all subfolders are unique
 *
 */

$controllerStack = new ControllerStack();

$controllerStack->setRootController(new RootController);

$controllerStack->addNamedController("admin", new AdminController);

$controllerStack->run();
