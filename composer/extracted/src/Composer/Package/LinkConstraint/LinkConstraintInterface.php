<?php











namespace Composer\Package\LinkConstraint;

use Composer\Semver\Constraint\ConstraintInterface;

trigger_error('The ' . __NAMESPACE__ . '\LinkConstraintInterface interface is deprecated, use Composer\Semver\Constraint\ConstraintInterface instead.', E_USER_DEPRECATED);




interface LinkConstraintInterface extends ConstraintInterface
{
}
