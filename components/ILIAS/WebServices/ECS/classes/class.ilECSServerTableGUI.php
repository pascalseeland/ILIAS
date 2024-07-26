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
class ilECSServerTableGUI
{
    private \ILIAS\UI\Factory $ui_factory;
    private \ILIAS\UI\Renderer $ui_renderer;
    private ilCtrlInterface $ctrl;
    private ilECSSettingsGUI $parent;
    private ilLanguage $lng;
    private array $ecs_list_items = [];
    private bool $with_actions;

    public function __construct(ilECSSettingsGUI $parent, ilECSServerSettings $servers, bool $with_actions = false)
    {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->parent = $parent;
        foreach ($servers->getServers(ilECSServerSettings::ALL_SERVER) as $server) {
            $this->ecs_list_items[] = $this->createItem($server);
        }

    }

    /**
     * Init Table
     */
    public function renderList(): string
    {
        $ecs_list_panel = $this->ui_factory->panel()->listing()->standard(
            $this->lng->txt('ecs_available_ecs'),
            [$this->ui_factory->item()->group('', $this->ecs_list_items)]
        );


        return $this->ui_renderer->render($ecs_list_panel);
    }

    private function createItem(ilECSSetting $ecs_server): \ILIAS\UI\Component\Item\Standard
    {

        $this->ctrl->setParameter($this->parent, 'server_id', $ecs_server->getServerId());
        $this->ctrl->setParameterByClass(ilECSSettingsGUI::class, 'server_id', $ecs_server->getServerId());
        // Actions
        $items = [];

        if ($ecs_server->isEnabled()) {
            $items[] = [$this->lng->txt('ecs_deactivate'), $this->ctrl->getLinkTarget($this->parent, 'deactivate')];
        } else {
            $items[] = [$this->lng->txt('ecs_activate'), $this->ctrl->getLinkTarget($this->parent, 'activate')];
        }

        $items[] = [$this->lng->txt('edit'), $this->ctrl->getLinkTarget($this->parent, 'edit')];
        $items[] = [$this->lng->txt('copy'), $this->ctrl->getLinkTarget($this->parent, 'cp')];
        $items[] = [$this->lng->txt('delete'), $this->ctrl->getLinkTarget($this->parent, 'delete')];

        $render_items = [];
        foreach ($items as $item) {
            $render_items[] = $this->ui_factory->button()->shy(...$item);
        }
        $actions = $this->ui_factory->dropdown()->standard($render_items)->withLabel($this->lng->txt('actions'));

        $icon_base_path = './templates/default/images/standard/icon_%s.svg';

        if ($ecs_server->isEnabled()) {
            $lead_icon = $this->ui_factory->symbol()->icon()->custom(sprintf($icon_base_path, 'ok'), $this->lng->txt('ecs_activated'));
        } else {
            $lead_icon = $this->ui_factory->symbol()->icon()->custom(sprintf($icon_base_path, 'not_ok'), $this->lng->txt('ecs_inactivated'));
        }

        $properties = [];
        $properties[$this->lng->txt('ecs_server_addr')] =
            $ecs_server->getServer() ?: $this->lng->txt('ecs_not_configured');

        $dt = $ecs_server->fetchCertificateExpiration();
        if ($dt !== null) {
            $now = new ilDateTime(time(), IL_CAL_UNIX);
            $now->increment(IL_CAL_MONTH, 2);
            $properties[$this->lng->txt('ecs_cert_valid_until')] =
                ilDateTime::_before($dt, $now) ?
                '<font class="smallred">'.ilDatePresentation::formatDate($dt).'</font>' :
                    ilDatePresentation::formatDate($dt);
        }

        $html_content =  $this->ui_factory->item()->standard(
            $this->ui_factory->link()->standard(
                $ecs_server->getTitle(),
                $this->ctrl->getLinkTarget($this->parent, 'edit')
            )
        )->withActions($actions)->withProperties($properties)->withLeadIcon($lead_icon);
        $this->ctrl->clearParameters($this->parent);
        return $html_content;
    }
}
