<?php










namespace Symfony\Component\Console\Question;






class ConfirmationQuestion extends Question
{
private $trueAnswerRegex;








public function __construct($question, $default = true, $trueAnswerRegex = '/^y/i')
{
parent::__construct($question, (bool) $default);

$this->trueAnswerRegex = $trueAnswerRegex;
$this->setNormalizer($this->getDefaultNormalizer());
}






private function getDefaultNormalizer()
{
$default = $this->getDefault();
$regex = $this->trueAnswerRegex;

return function ($answer) use ($default, $regex) {
if (is_bool($answer)) {
return $answer;
}

$answerIsTrue = (bool) preg_match($regex, $answer);
if (false === $default) {
return $answer && $answerIsTrue;
}

return !$answer || $answerIsTrue;
};
}
}
