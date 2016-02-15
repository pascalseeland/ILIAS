<#1>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
ilDBUpdateNewObjectType::addAdminNode('bdga', 'Badge Settings');

?>
<#2>
<?php

$ilCtrlStructureReader->getStructure();

?>
<#3>
<?php

if(!$ilDB->tableExists('badge_badge'))
{
	$ilDB->createTable('badge_badge', array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'parent_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'type_id' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false
		),
		'active' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),		
		'title' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false
		),
		'descr' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'conf' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		)
	));	
	$ilDB->addPrimaryKey('badge_badge',array('id'));
	$ilDB->createSequence('badge_badge');
}

?>
<#4>
<?php

if(!$ilDB->tableExists('badge_image_template'))
{
	$ilDB->createTable('badge_image_template', array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),		
		'title' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false
		),
		'image' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false
		)
	));	
	$ilDB->addPrimaryKey('badge_image_template',array('id'));
	$ilDB->createSequence('badge_image_template');
}

?>
<#5>
<?php

if(!$ilDB->tableColumnExists('badge_badge','image')) 
{
    $ilDB->addTableColumn(
        'badge_badge',
        'image',
        array(
            'image' => array(
				'type' => 'text',
				'length' => 255,
				'notnull' => false)	
        ));
}

?>
<#6>
<?php

if(!$ilDB->tableExists('badge_image_templ_type'))
{
	$ilDB->createTable('badge_image_templ_type', array(
		'tmpl_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'type_id' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true,
			'default' => ""
		)
	));	
	$ilDB->addPrimaryKey('badge_image_templ_type',array('tmpl_id', 'type_id'));
}

?>