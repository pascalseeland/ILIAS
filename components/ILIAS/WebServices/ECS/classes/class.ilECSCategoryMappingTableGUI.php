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

use ILIAS\UI\URLBuilder;

/**
* Show active rules
*
* @author Stefan Meyer <meyer@leifos.com>
*/
class ilECSCategoryMappingTableGUI
{
    private ilLogger $logger;
    private \ILIAS\UI\Factory $ui_factory;
    private \ILIAS\UI\Renderer $ui_renderer;
    private ilLanguage $lng;
    private \ILIAS\UI\Component\Table\Data $table;
    private \Psr\Http\Message\ServerRequestInterface|\Psr\Http\Message\RequestInterface $request;
    private ?\ILIAS\UI\URLBuilderToken $action_token = null;
    private ?\ILIAS\UI\URLBuilderToken $id_token = null;
    private \ILIAS\Refinery\Factory $refinery;
    private \ILIAS\HTTP\Wrapper\WrapperFactory $http_wrapper;
    private ilCtrlInterface $ctrl;
    private ilECSSettingsGUI $parent;

    /**
     * @param ilECSCategoryMappingRule[] $active_rules the rules to be displayed
     */
    public function __construct(ilECSSettingsGUI $a_parent_obj, string $a_parent_cmd, array $active_rules)
    {
        global $DIC;
        $this->logger = $DIC->logger()->wsrv();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->lng = $DIC->language();
        $this->request = $DIC->http()->request();
        $this->http_wrapper = $DIC->http()->wrapper();
        $this->refinery = $DIC->refinery();
        $this->ctrl = $DIC->ctrl();
        $this->parent = $a_parent_obj;
        $df = new \ILIAS\Data\Factory();

        /*$this->addColumn('', 'f', '1px');
        $this->addColumn($this->lng->txt('obj_cat'), 'category', '40%');
        $this->addColumn($this->lng->txt('ecs_cat_mapping_type'), 'kind', '50%');
        $this->addColumn('', 'edit', '10%');
        $this->setRowTemplate('tpl.rule_row.html', 'components/ILIAS/WebServices/ECS');
        $this->setDefaultOrderField('title');
        $this->setDefaultOrderDirection('asc');a
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->setSelectAllCheckbox('rules');
        $this->setTitle($this->lng->txt('ecs_tbl_active_rules'));
        $this->addMultiCommand('deleteCategoryMappings', $this->lng->txt('delete'));*/

        $columns = [
            'title' => $this->ui_factory->table()->column()
                                                  ->text($this->lng->txt('title'))
                                                  ->withIsSortable(false),
            'ecs_import_id' => $this->ui_factory->table()->column()->number($this->lng->txt('ecs_import_id'))
                    ->withIsSortable(false),
            'repository_path' => $this->ui_factory->table()->column()->link("Path")
                ->withIsSortable(false),
            'assignment' => $this->ui_factory->table()->column()
                                                  ->text($this->lng->txt('ecs_cat_mapping_type'))
                                                  ->withIsSortable(false)
        ];
        $here_uri = $df->uri($DIC->http()->request()->getUri()->__toString());
        $url_builder = new URLBuilder($here_uri);
        $query_params_namespace = ['ecs', 'categorie_mapping'];
        [$url_builder, $this->id_token, $this->action_token] = $url_builder->acquireParameters(
            $query_params_namespace,
            'id',
            'action'
        );
        $uri_detach = $df->uri(
            ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass(
                $this->parent::class,
                'editCategoryMapping'
            )
        );

        $url_builder_detach = new URLBuilder($uri_detach);
        [
            $url_builder_detach,
            $action_parameter_token_copy,
            $row_id_token_detach
        ] = $url_builder_detach->acquireParameters(
            $query_params_namespace,
            'action',
            'mapping_ids'
        );
        $actions = [
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('edit'),
                $url_builder_detach->withParameter($action_parameter_token_copy, 'edit'),
                $row_id_token_detach
            ),
            'delete' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('delete'),
                $url_builder->withParameter($this->action_token, "delete"),
                $this->id_token
            )->withAsync()
        ];
        $data_retrieval = new class($this->ui_factory, $this->ui_renderer, $active_rules) implements ILIAS\UI\Component\Table\DataRetrieval {
            public function __construct(
                protected \ILIAS\UI\Factory $ui_factory,
                protected \ILIAS\UI\Renderer $ui_renderer,
                protected array $active_rules
            ) {
            }

            private function buildPath(int $a_ref_id): array
            {
                $loc = new ilLocatorGUI();
                $loc->setTextOnly(false);
                $loc->addContextItems($a_ref_id);

                return $loc->getItems();
            }

            public function getRows(
                \ILIAS\UI\Component\Table\DataRowBuilder $row_builder,
                array $visible_column_ids,
                \ILIAS\Data\Range $range,
                \ILIAS\Data\Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ) : Generator {
                $records = $this->getRecords($range, $order);
                foreach ($records as $record) {
                    $row_id = (string)$record->getMappingId();
                    $array = $this->buildPath($record->getContainerId());
                    $link = array_pop($array);
                    $render_record = [];
                    $render_record['title'] = ilObject::_lookupTitle(ilObject::_lookupObjId($record->getContainerId()));
                    $render_record['ecs_import_id'] = $record->getContainerId();
                    $render_record['repository_path'] = $this->ui_factory->link()->standard($link["title"], $link["link"]);
                    $render_record['assignment'] = $record->conditionToString();

                    yield $row_builder->buildDataRow($row_id, $render_record);
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int {
                return count($this->getRecords());
            }
            /**
             * get records
             *
             * @return ilECSCategoryMappingRule[]
             */
            protected function getRecords(\ILIAS\Data\Range $range = null, \ILIAS\Data\Order $order = null): array
            {

                if ($order) {
                    [$order_field, $order_direction] = $order->join([], fn ($ret, $key, $value) => [$key, $value]);
                    usort(
                        $this->active_rules,
                        static fn (ilECSCategoryMappingRule $a, ilECSCategoryMappingRule $b) =>
                        ilObject::_lookupTitle(ilObject::_lookupObjId($a->getContainerId())) <=> ilObject::_lookupTitle(ilObject::_lookupObjId($b->getContainerId()))
                    );
                    if ($order_direction === 'DESC') {
                        $records = array_reverse($this->active_rules);
                    }
                }
                if ($range) {
                    $records = array_slice($this->active_rules, $range->getStart(), $range->getLength());
                }

                return $this->active_rules;
            }
        };
        $this->table = $this->ui_factory->table()
                   ->data($this->lng->txt('ecs_tbl_active_rules'), $columns, $data_retrieval)
                   ->withId('ecs_active_mapping_rules')->withActions($actions)->withRequest($this->request);
    }

    public function render(): string
    {
        $query = $this->http_wrapper->query();
        if ($query->has($this->action_token->getName())) {
            $action = $query->retrieve($this->action_token->getName(), $this->refinery->to()->string());
            $id = $query->retrieve($this->id_token->getName(), $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()));

            if ($action === 'delete') {
                echo($this->ui_renderer->renderAsync([
                    $this->ui_factory->modal()->interruptive(
                        'do something else',
                        'affected items',
                        '#'
                    )->withAffectedItems([$id])
                ]));
                exit();
            }
        }
        return $this->ui_renderer->render($this->table);
    }
    /*  protected function fillRow(array $a_set): void
      {
          $this->tpl->setVariable('VAL_ID', $a_set['id']);
          $this->tpl->setVariable('TXT_ID', $this->lng->txt('ecs_import_id'));
          $this->tpl->setVariable('VAL_CAT_ID', $a_set['category_id']);
          $this->tpl->setVariable('TXT_TITLE', $this->lng->txt('title'));
          $this->tpl->setVariable('VAL_CAT_TITLE', $a_set['category']);
          $this->tpl->setVariable('VAL_CONDITION', $a_set['kind']);
          $this->tpl->setVariable('TXT_EDIT', $this->lng->txt('edit'));
          $this->tpl->setVariable('PATH', $this->buildPath($a_set['category_id']));
          if ($this->getParentObject()) {
              $this->ctrl->setParameterByClass(get_class($this->getParentObject()), 'rule_id', $a_set['id']);
              $this->tpl->setVariable(
                  'EDIT_LINK',
                  $this->ctrl->getLinkTargetByClass(get_class($this->getParentObject()), 'editCategoryMapping')
              );
              $this->ctrl->clearParametersByClass(get_class($this->getParentObject()));
          } else {
              $this->logger->error("Cannot fill Category Mapping Table due to parent object being null");
          }
      }*/
}
