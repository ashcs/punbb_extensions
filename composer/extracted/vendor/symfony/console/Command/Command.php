<?php










namespace Symfony\Component\Console\Command;

use Symfony\Component\Console\Descriptor\TextDescriptor;
use Symfony\Component\Console\Descriptor\XmlDescriptor;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;






class Command
{
private $application;
private $name;
private $processTitle;
private $aliases = array();
private $definition;
private $help;
private $description;
private $ignoreValidationErrors = false;
private $applicationDefinitionMerged = false;
private $applicationDefinitionMergedWithArgs = false;
private $code;
private $synopsis = array();
private $usages = array();
private $helperSet;








public function __construct($name = null)
{
$this->definition = new InputDefinition();

if (null !== $name) {
$this->setName($name);
}

$this->configure();

if (!$this->name) {
throw new LogicException(sprintf('The command defined in "%s" cannot have an empty name.', get_class($this)));
}
}






public function ignoreValidationErrors()
{
$this->ignoreValidationErrors = true;
}






public function setApplication(Application $application = null)
{
$this->application = $application;
if ($application) {
$this->setHelperSet($application->getHelperSet());
} else {
$this->helperSet = null;
}
}






public function setHelperSet(HelperSet $helperSet)
{
$this->helperSet = $helperSet;
}






public function getHelperSet()
{
return $this->helperSet;
}






public function getApplication()
{
return $this->application;
}









public function isEnabled()
{
return true;
}




protected function configure()
{
}


















protected function execute(InputInterface $input, OutputInterface $output)
{
throw new LogicException('You must override the execute() method in the concrete command class.');
}











protected function interact(InputInterface $input, OutputInterface $output)
{
}










protected function initialize(InputInterface $input, OutputInterface $output)
{
}


















public function run(InputInterface $input, OutputInterface $output)
{

 $this->getSynopsis(true);
$this->getSynopsis(false);


 $this->mergeApplicationDefinition();


 try {
$input->bind($this->definition);
} catch (ExceptionInterface $e) {
if (!$this->ignoreValidationErrors) {
throw $e;
}
}

$this->initialize($input, $output);

if (null !== $this->processTitle) {
if (function_exists('cli_set_process_title')) {
cli_set_process_title($this->processTitle);
} elseif (function_exists('setproctitle')) {
setproctitle($this->processTitle);
} elseif (OutputInterface::VERBOSITY_VERY_VERBOSE === $output->getVerbosity()) {
$output->writeln('<comment>Install the proctitle PECL to be able to change the process title.</comment>');
}
}

if ($input->isInteractive()) {
$this->interact($input, $output);
}


 
 
 if ($input->hasArgument('command') && null === $input->getArgument('command')) {
$input->setArgument('command', $this->getName());
}

$input->validate();

if ($this->code) {
$statusCode = call_user_func($this->code, $input, $output);
} else {
$statusCode = $this->execute($input, $output);
}

return is_numeric($statusCode) ? (int) $statusCode : 0;
}















public function setCode($code)
{
if (!is_callable($code)) {
throw new InvalidArgumentException('Invalid callable provided to Command::setCode.');
}

if (PHP_VERSION_ID >= 50400 && $code instanceof \Closure) {
$r = new \ReflectionFunction($code);
if (null === $r->getClosureThis()) {
$code = \Closure::bind($code, $this);
}
}

$this->code = $code;

return $this;
}








public function mergeApplicationDefinition($mergeArgs = true)
{
if (null === $this->application || (true === $this->applicationDefinitionMerged && ($this->applicationDefinitionMergedWithArgs || !$mergeArgs))) {
return;
}

if ($mergeArgs) {
$currentArguments = $this->definition->getArguments();
$this->definition->setArguments($this->application->getDefinition()->getArguments());
$this->definition->addArguments($currentArguments);
}

$this->definition->addOptions($this->application->getDefinition()->getOptions());

$this->applicationDefinitionMerged = true;
if ($mergeArgs) {
$this->applicationDefinitionMergedWithArgs = true;
}
}








public function setDefinition($definition)
{
if ($definition instanceof InputDefinition) {
$this->definition = $definition;
} else {
$this->definition->setDefinition($definition);
}

$this->applicationDefinitionMerged = false;

return $this;
}






public function getDefinition()
{
return $this->definition;
}











public function getNativeDefinition()
{
return $this->getDefinition();
}











public function addArgument($name, $mode = null, $description = '', $default = null)
{
$this->definition->addArgument(new InputArgument($name, $mode, $description, $default));

return $this;
}












public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
{
$this->definition->addOption(new InputOption($name, $shortcut, $mode, $description, $default));

return $this;
}















public function setName($name)
{
$this->validateName($name);

$this->name = $name;

return $this;
}













public function setProcessTitle($title)
{
$this->processTitle = $title;

return $this;
}






public function getName()
{
return $this->name;
}








public function setDescription($description)
{
$this->description = $description;

return $this;
}






public function getDescription()
{
return $this->description;
}








public function setHelp($help)
{
$this->help = $help;

return $this;
}






public function getHelp()
{
return $this->help;
}







public function getProcessedHelp()
{
$name = $this->name;

$placeholders = array(
'%command.name%',
'%command.full_name%',
);
$replacements = array(
$name,
$_SERVER['PHP_SELF'].' '.$name,
);

return str_replace($placeholders, $replacements, $this->getHelp() ?: $this->getDescription());
}










public function setAliases($aliases)
{
if (!is_array($aliases) && !$aliases instanceof \Traversable) {
throw new InvalidArgumentException('$aliases must be an array or an instance of \Traversable');
}

foreach ($aliases as $alias) {
$this->validateName($alias);
}

$this->aliases = $aliases;

return $this;
}






public function getAliases()
{
return $this->aliases;
}








public function getSynopsis($short = false)
{
$key = $short ? 'short' : 'long';

if (!isset($this->synopsis[$key])) {
$this->synopsis[$key] = trim(sprintf('%s %s', $this->name, $this->definition->getSynopsis($short)));
}

return $this->synopsis[$key];
}






public function addUsage($usage)
{
if (0 !== strpos($usage, $this->name)) {
$usage = sprintf('%s %s', $this->name, $usage);
}

$this->usages[] = $usage;

return $this;
}






public function getUsages()
{
return $this->usages;
}










public function getHelper($name)
{
return $this->helperSet->get($name);
}








public function asText()
{
@trigger_error('The '.__METHOD__.' method is deprecated since version 2.3 and will be removed in 3.0.', E_USER_DEPRECATED);

$descriptor = new TextDescriptor();
$output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
$descriptor->describe($output, $this, array('raw_output' => true));

return $output->fetch();
}










public function asXml($asDom = false)
{
@trigger_error('The '.__METHOD__.' method is deprecated since version 2.3 and will be removed in 3.0.', E_USER_DEPRECATED);

$descriptor = new XmlDescriptor();

if ($asDom) {
return $descriptor->getCommandDocument($this);
}

$output = new BufferedOutput();
$descriptor->describe($output, $this);

return $output->fetch();
}










private function validateName($name)
{
if (!preg_match('/^[^\:]++(\:[^\:]++)*$/', $name)) {
throw new InvalidArgumentException(sprintf('Command name "%s" is invalid.', $name));
}
}
}
