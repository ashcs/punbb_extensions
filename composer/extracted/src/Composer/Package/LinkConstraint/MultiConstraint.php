<?php











namespace Composer\Package\LinkConstraint;

use Composer\Semver\Constraint\MultiConstraint as SemverMultiConstraint;

trigger_error('The ' . __NAMESPACE__ . '\MultiConstraint class is deprecated, use Composer\Semver\Constraint\MultiConstraint instead.', E_USER_DEPRECATED);




class MultiConstraint extends SemverMultiConstraint implements LinkConstraintInterface
{
}
