<?php











namespace Composer\Package\LinkConstraint;

use Composer\Semver\Constraint\AbstractConstraint;

trigger_error('The ' . __NAMESPACE__ . '\SpecificConstraint abstract class is deprecated, there is no replacement for it.', E_USER_DEPRECATED);




abstract class SpecificConstraint extends AbstractConstraint implements LinkConstraintInterface
{
}
