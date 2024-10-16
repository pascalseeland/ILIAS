<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package components\ILIAS/Test
 */
abstract class ilTestProcessLocker
{
    /**
     * @param callable $operation
     */
    protected function executeOperation(callable $operation)
    {
        $operation();
    }

    /**
     * @param callable $operation
     */
    final public function executeTestStartLockOperation(callable $operation)
    {
        $this->onBeforeExecutingTestStartOperation();
        $this->executeOperation($operation);
        $this->onAfterExecutingTestStartOperation();
    }

    /**
     *
     */
    protected function onBeforeExecutingTestStartOperation()
    {
    }

    /**
     *
     */
    protected function onAfterExecutingTestStartOperation()
    {
    }

    /**
     * @param callable $operation
     * @param bool     $withTaxonomyTables
     */
    final public function executeRandomPassBuildOperation(callable $operation, $withTaxonomyTables = false)
    {
        $this->onBeforeExecutingRandomPassBuildOperation($withTaxonomyTables);
        $this->executeOperation($operation);
        $this->onAfterExecutingRandomPassBuildOperation($withTaxonomyTables);
    }

    /**
     * @param bool $withTaxonomyTables
     */
    protected function onBeforeExecutingRandomPassBuildOperation($withTaxonomyTables = false)
    {
    }

    /**
     * @param bool $withTaxonomyTables
     */
    protected function onAfterExecutingRandomPassBuildOperation($withTaxonomyTables = false)
    {
    }


    /**
     * @param callable $operation
     */
    final public function executeTestFinishOperation(callable $operation)
    {
        $this->onBeforeExecutingTestFinishOperation();
        $this->executeOperation($operation);
        $this->onAfterExecutingTestFinishOperation();
    }

    final public function executeNamedOperation(string $operationDescriptor, callable $operation): void
    {
        $this->onBeforeExecutingNamedOperation($operationDescriptor);
        $this->executeOperation($operation);
        $this->onAfterExecutingNamedOperation($operationDescriptor);
    }

    /**
     *
     */
    protected function onBeforeExecutingTestFinishOperation()
    {
    }

    /**
     *
     */
    protected function onAfterExecutingTestFinishOperation()
    {
    }

    protected function onBeforeExecutingNamedOperation(string $operationDescriptor): void
    {
    }

    protected function onAfterExecutingNamedOperation(string $operationDescriptor): void
    {
    }
}
