<?php











namespace Composer\Package\LinkConstraint;

use Composer\Semver\Constraint\EmptyConstraint as SemverEmptyConstraint;

trigger_error('The ' . __NAMESPACE__ . '\EmptyConstraint class is deprecated, use Composer\Semver\Constraint\EmptyConstraint instead.', E_USER_DEPRECATED);




class EmptyConstraint extends SemverEmptyConstraint implements LinkConstraintInterface
{
}
