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
 ********************************************************************
 */

/**
 * Class ilOrgUnitSimpleUserImport
 * @author : Oskar Truffer <ot@studer-raimann.ch>
 * @author : Martin Studer <ms@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilOrgUnitSimpleUserImport extends ilOrgUnitImporter
{
    protected \ilOrgUnitPositionDBRepository $positionRepo;
    protected \ilOrgUnitUserAssignmentDBRepository $assignmentRepo;

    public function __construct()
    {
        $dic = ilOrgUnitLocalDIC::dic();
        $this->positionRepo = $dic["repo.Positions"];
        $this->assignmentRepo = $dic["repo.UserAssignments"];
    }

    /**
     * @param $file_path
     */
    public function simpleUserImport($file_path)
    {
        $this->stats = array('created' => 0, 'removed' => 0);
        $a = file_get_contents($file_path, 'r');
        $xml = new SimpleXMLElement($a);

        if (!count($xml->children())) {
            $this->addError('no_assignment', null, null);

            return;
        }

        foreach ($xml->children() as $a) {
            $this->simpleUserImportElement($a);
        }
    }

    /**
     * @param SimpleXMLElement $a
     */
    public function simpleUserImportElement(SimpleXMLElement $a)
    {
        $attributes = $a->attributes();
        $action = $attributes->action;
        $user_id_type = $a->User->attributes()->id_type;
        $user_id = (string) $a->User;
        $org_unit_id_type = $a->OrgUnit->attributes()->id_type;
        $org_unit_id = (string) $a->OrgUnit;
        $role = (string) $a->Role;

        if (!$user_id = $this->buildUserId($user_id, $user_id_type)) {
            $this->addError('user_not_found', $a->User);

            return;
        }

        if (!$org_unit_id = $this->buildRef($org_unit_id, $org_unit_id_type)) {
            $this->addError('org_unit_not_found', $a->OrgUnit);

            return;
        }
        $org_unit = new ilObjOrgUnit($org_unit_id);

        if ($role === 'employee') {
            $position_id = $this->positionRepo
                ->getSingle(ilOrgUnitPosition::CORE_POSITION_EMPLOYEE, 'core_identifier')
                ->getId();
        } elseif ($role === 'superior') {
            $position_id = $this->positionRepo
                ->getSingle(ilOrgUnitPosition::CORE_POSITION_SUPERIOR, 'core_identifier')
                ->getId();
        } else {
            //if passed a custom position.
            $position = $this->positionRepo->getSingle($role, 'title');
            if ($position instanceof ilOrgUnitPosition) {
                $position_id = $position->getId();
            } else {
                $this->addError('not_a_valid_role', $user_id);
                return;
            }
        }

        if ($action == 'add') {
            $assignment = $this->assignmentRepo->get($user_id, $position_id, $org_unit_id);
            $this->stats['created']++;
        } elseif ($action == 'remove') {
            $assignment = $this->assignmentRepo->find($user_id, $position_id, $org_unit_id);
            if ($assignment) {
                $this->assignmentRepo->delete($assignment);
                $this->stats['removed']++;
            }
        } else {
            $this->addError('not_a_valid_action', $user_id);
        }
    }

    /**
     * @param $id
     * @param $type
     * @return bool
     */
    private function buildUserId($id, $type)
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        if ($type == 'ilias_login') {
            $user_id = ilObjUser::_lookupId($id);

            return $user_id ? $user_id : false;
        } elseif ($type == 'external_id') {
            $user_id = ilObjUser::_lookupObjIdByImportId($id);

            return $user_id ? $user_id : false;
        } elseif ($type == 'email') {
            $q = 'SELECT usr_id FROM usr_data WHERE email = ' . $ilDB->quote($id, 'text');
            $set = $ilDB->query($q);
            $user_id = $ilDB->fetchAssoc($set);

            return $user_id ? $user_id : false;
        } elseif ($type == 'user_id') {
            return $id;
        } else {
            return false;
        }
    }
}
