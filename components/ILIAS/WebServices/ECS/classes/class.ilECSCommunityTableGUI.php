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
 */

declare(strict_types=1);

/**
 * @author Stefan Meyer <meyer@leifos.com>
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 */
class ilECSCommunityTableGUI
{
    private ilECSSetting $server;

    private \ILIAS\UI\Factory $ui_factory;
    private \ILIAS\UI\Renderer $ui_renderer;
    private array $server_infos;
    private ilLanguage $lng;
    private bool $with_actions;
    private ilECSSettingsGUI $parent;
    private ilCtrlInterface $ctrl;

    public function __construct(ilECSSettingsGUI $parent, bool $with_actions = false)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('administration');

        $this->parent = $parent;
        $this->with_actions = $with_actions;

        $this->generateRenderData();
    }

    public function render(): string
    {
        $mapping_closure = function (\ILIAS\UI\Component\Table\PresentationRow $row, $record, $ui_factory, $environment) {
            $row = $row
                ->withHeadline($this->lng->txt('ecs_participants').': '.$record['participant_name'])
                ->withSubheadline($this->lng->txt('ecs_tbl_import_type') .': '.$record['import_type'])
                ->withImportantFields([
                   "Imported types" => $record['imported_types'],
                    "Exported types" => $record['exported_types']])
                ->withContent($this->ui_factory->listing()->descriptive(
                    [
                        "Imported type" => $record['imported_types'],
                        "Exported types" => $record['exported_types']
                    ]
                ))
                ->withFurtherFieldsHeadline($this->lng->txt('ecs_participants_infos'))
                ->withFurtherFields($record['participant_info']);
            if ($this->with_actions) {
                $row = $row->withAction(
                    $this->ui_factory->dropdown()->standard(
                        array_map(function ($action) {
                            return $this->ui_factory->button()->shy($action[0], $action[1]);
                        }, $record['actions'])
                    )->withLabel($this->lng->txt('actions'))
                );
            }
            return $row;
        };
        $ptable = $this->ui_factory->table()->presentation(
            "",
            [],
            $mapping_closure
        );
        $server_panels = [];
        foreach ($this->server_infos as $server_info) {
            $community_tables = [];
            foreach($server_info['communities'] as $community) {
                $community_tables[] = $ptable
                    ->withData([...$community['participants']])
                    ->withTitle("Community: ". $community['title']);
            }
            $panel = $this->ui_factory->panel()->standard(
                'ECS-Server: '.$server_info['title'],
                $community_tables
            );
            $server_panels[] = $panel;
        }
        return $this->ui_renderer->render($server_panels);
    }

    private function generateActions(int $server_id, int $mid): array
    {
        $part = new ilECSParticipantSetting($server_id, $mid);


        $items = [];
        $this->ctrl->setParameter($this->parent, 'server_id', $server_id);
        $this->ctrl->setParameter($this->parent, 'mid', $mid);
        $items[] = [ $this->lng->txt('ecs_edit_import_type'),
                     $this->ctrl->getLinkTargetByClass(ilECSParticipantSettingsGUI::class, 'importtype')
        ];
        $items[] = [ $this->lng->txt('edit'),
            $this->ctrl->getLinkTargetByClass(ilECSParticipantSettingsGUI::class, 'settings')
        ];

        switch ($part->getImportType()) {
            case ilECSParticipantSetting::IMPORT_RCRS:
                // Do nothing
                break;

            case ilECSParticipantSetting::IMPORT_CRS:
                // Possible action => Edit course allocation
                $items[] = [
                    $this->lng->txt('ecs_crs_alloc_set'),
                    $this->ctrl->getLinkTargetByClass('ilecsmappingsettingsgui', 'cStart')
                ];
                break;

            case ilECSParticipantSetting::IMPORT_CMS:
                // Possible action => Edit course allocation, edit node mapping
                $items[] = [
                    $this->lng->txt('ecs_dir_alloc_set'),
                    $this->ctrl->getLinkTargetByClass('ilecsmappingsettingsgui', 'dStart')
                ];
                $items[] = [
                    $this->lng->txt('ecs_crs_alloc_set'),
                    $this->ctrl->getLinkTargetByClass('ilecsmappingsettingsgui', 'cStart')
                ];
                break;
        }
        return $items;
    }

    private function generateRenderData(): void
    {
        $settings = ilECSServerSettings::getInstance();

        $server_infos = [];
        foreach ($settings->getServers(ilECSServerSettings::ALL_SERVER) as $server) {
            // Try to read communities
            try {
                $reader = ilECSCommunityReader::getInstanceByServerId($server->getServerId());
                $server_communities = [];
                foreach ($reader->getCommunities() as $community) {
                    $community_info = [];
                    $community_info['title'] = $community->getTitle();
                    $community_info['description'] = $community->getDescription();
                    $community_info['participants'] = [];
                    foreach ($community->getParticipants() as $participant) {
                        $part = new ilECSParticipantSetting($server->getServerId(), $participant->getMID());
                        $tmp_arr['mid'] = $participant->getMID();
                        $tmp_arr['participant_name'] = $participant->getParticipantName();
                        $participant_info = [];
                        $participant_info['description'] = $participant->getDescription();
                        if ($participant->getOrganisation() instanceof ilECSOrganisation) {
                            $participant_info[$this->lng->txt('organization')] =
                                $participant->getOrganisation()->getName() .
                                ' (' . $participant->getOrganisation()->getAbbreviation() . ')';
                        }
                        $participant_info[$this->lng->txt('ecs_unique_id')] = $server->getServerId() . '_' . $participant->getMID();
                        $participant_info[$this->lng->txt('ecs_email')] = $participant->getEmail();
                        $participant_info[$this->lng->txt('ecs_dns')] = $participant->getDNS();
                        $tmp_arr['participant_info'] = $participant_info;
                        switch ($part->getImportType()) {
                            case ilECSParticipantSetting::IMPORT_RCRS:
                                $tmp_arr['import_type'] = $this->lng->txt('obj_rcrs');
                                break;
                            case ilECSParticipantSetting::IMPORT_CRS:
                                $tmp_arr['import_type'] = $this->lng->txt('obj_crs');
                                break;
                            case ilECSParticipantSetting::IMPORT_CMS:
                                $tmp_arr['import_type'] = $this->lng->txt('ecs_import_cms');
                                break;
                        }
                        if ($part->isImportEnabled()) {
                            $imported_types = [];
                            foreach ($part->getImportTypes() as $obj_type) {
                                $imported_types[] = $this->lng->txt('objs_' . $obj_type);
                            }
                            $tmp_arr['imported_types'] = implode(', ', $imported_types);
                        } else {
                            $tmp_arr['imported_types'] = $this->lng->txt('disabled');
                        }
                        if ($part->isExportEnabled()) {
                            $exported_types = [];
                            foreach ($part->getExportTypes() as $obj_type) {
                                $exported_types[] = $this->lng->txt('objs_' . $obj_type);
                            }
                            $tmp_arr['exported_types'] = implode(', ', $exported_types);
                        } else {

                            $tmp_arr['exported_types'] = $this->lng->txt('disabled');
                        }
                        if ($this->with_actions) {
                            $tmp_arr['actions'] = $this->generateActions($server->getServerId(), $participant->getMID());
                        }
                        $community_info['participants'][$participant->getMID()] = $tmp_arr;
                    }
                    $server_communities[$community->getId()] = $community_info;
                }
                $server_infos[$server->getServerId()] = [];
                $server_infos[$server->getServerId()]['title'] = $server->getTitle();
                $server_infos[$server->getServerId()]['communities'] = $server_communities;
            } catch (ilECSConnectorException $exc) {
                // Maybe server is not fully configured
            }
        }
        $this->server_infos = $server_infos;
    }
}
