--TEST--
QuestionHelper should parse special chars on windows input.
--STDIN--
à é
--FILE--
<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

$vendor = __DIR__;
while (!file_exists($vendor.'/vendor')) {
    $vendor = dirname($vendor);
}
require $vendor.'/vendor/autoload.php';

$output = new ConsoleOutput();
$output->write((new QuestionHelper())->ask(new ArgvInput(), $output, new Question("Special chars\n")));
exit(0);
?>
--EXPECTF--
Special chars
à é
