<?php











namespace Composer\IO;

use Composer\Config;






interface IOInterface
{
const QUIET = 1;
const NORMAL = 2;
const VERBOSE = 4;
const VERY_VERBOSE = 8;
const DEBUG = 16;






public function isInteractive();






public function isVerbose();






public function isVeryVerbose();






public function isDebug();






public function isDecorated();








public function write($messages, $newline = true, $verbosity = self::NORMAL);








public function writeError($messages, $newline = true, $verbosity = self::NORMAL);









public function overwrite($messages, $newline = true, $size = null, $verbosity = self::NORMAL);









public function overwriteError($messages, $newline = true, $size = null, $verbosity = self::NORMAL);










public function ask($question, $default = null);











public function askConfirmation($question, $default = true);
















public function askAndValidate($question, $validator, $attempts = null, $default = null);








public function askAndHideAnswer($question);






public function getAuthentications();








public function hasAuthentication($repositoryName);








public function getAuthentication($repositoryName);








public function setAuthentication($repositoryName, $username, $password = null);






public function loadConfiguration(Config $config);
}
