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
 * Class ilObjTestVerificationTest
 * @author Marvin Beym <mbeym@databay.de>
 */
class ilObjTestVerificationTest extends ilTestBaseTestCase
{
    private ilObjTestVerification $testObj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addGlobal_ilDB();
        $this->addGlobal_ilias();
        $this->addGlobal_ilErr();
        $this->addGlobal_tree();
        $this->addGlobal_ilLog();
        $this->addGlobal_ilAppEventHandler();
        $this->addGlobal_objDefinition();

        $this->testObj = new ilObjTestVerification();
    }

    public function test_instantiateObject_shouldReturnInstance(): void
    {
        $this->assertInstanceOf(ilObjTestVerification::class, $this->testObj);
    }
}
