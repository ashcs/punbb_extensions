<?php











namespace Composer\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;




class ScriptAliasCommand extends Command
{
private $script;

public function __construct($script)
{
$this->script = $script;

parent::__construct();
}

protected function configure()
{
$this
->setName($this->script)
->setDescription('Run the '.$this->script.' script as defined in composer.json.')
->setDefinition(array(
new InputOption('dev', null, InputOption::VALUE_NONE, 'Sets the dev mode.'),
new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables the dev mode.'),
new InputArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, ''),
))
->setHelp(<<<EOT
The <info>run-script</info> command runs scripts defined in composer.json:

<info>php composer.phar run-script post-update-cmd</info>
EOT
)
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{
$composer = $this->getComposer();

$args = $input->getArguments();

return $composer->getEventDispatcher()->dispatchScript($this->script, $input->getOption('dev') || !$input->getOption('no-dev'), $args['args']);
}
}
