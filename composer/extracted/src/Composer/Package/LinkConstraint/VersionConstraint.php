<?php











namespace Composer\Package\LinkConstraint;

use Composer\Semver\Constraint\Constraint;

trigger_error('The ' . __NAMESPACE__ . '\VersionConstraint class is deprecated, use Composer\Semver\Constraint\Constraint instead.', E_USER_DEPRECATED);




class VersionConstraint extends Constraint implements LinkConstraintInterface
{
}
