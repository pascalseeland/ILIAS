<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Object/classes/class.ilObjectGUI.php");
include_once("./Services/Badge/classes/class.ilBadgeHandler.php");

/**
 * Badge Administration Settings.
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id:$
 *
 * @ilCtrl_Calls ilObjBadgeAdministrationGUI: ilPermissionGUI, ilBadgeManagementGUI
 * @ilCtrl_IsCalledBy ilObjBadgeAdministrationGUI: ilAdministrationGUI
 *
 * @ingroup ServicesBadge
 */
class ilObjBadgeAdministrationGUI extends ilObjectGUI
{	
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		$this->type = "bdga";
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng->loadLanguageModule("badge");
	}

	public function executeCommand()
	{		
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui = new ilPermissionGUI($this);
				$this->ctrl->forwardCommand($perm_gui);
				break;
			
			case 'ilbadgemanagementgui':
				$this->assertActive();
				$this->tabs_gui->setTabActive('activity');
				include_once "Services/Badge/classes/class.ilBadgeManagementGUI.php";
				$gui = new ilBadgeManagementGUI($this->ref_id, $this->obj_id, $this->type);
				$this->ctrl->forwardCommand($gui);
				break;

			default:
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "editSettings";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	public function getAdminTabs()
	{
		global $rbacsystem;

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->tabs_gui->addTab("settings",
				$this->lng->txt("settings"),
				$this->ctrl->getLinkTarget($this, "editSettings"));
			
			if(ilBadgeHandler::getInstance()->isActive())
			{			
				$this->tabs_gui->addTab("types",
					$this->lng->txt("badge_types"),
					$this->ctrl->getLinkTarget($this, "listTypes"));

				$this->tabs_gui->addTab("imgtmpl",
					$this->lng->txt("badge_image_templates"),
					$this->ctrl->getLinkTarget($this, "listImageTemplates"));

				$this->tabs_gui->addTab("activity",
					$this->lng->txt("badge_activity_badges"),
					$this->ctrl->getLinkTargetByClass("ilbadgemanagementgui", ""));
			}
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$this->tabs_gui->addTab("perm_settings",
				$this->lng->txt("perm_settings"),
				$this->ctrl->getLinkTargetByClass('ilpermissiongui',"perm"));
		}
	}
	
	protected function assertActive()
	{
		if(!ilBadgeHandler::getInstance()->isActive())
		{
			$this->ctrl->redirect($this, "editSettings");
		}		
	}
	
	
	//
	// settings
	//

	protected function editSettings($a_form = null)
	{		
		$this->tabs_gui->setTabActive("settings");	
		
		if(!$a_form)
		{
			$a_form = $this->initFormSettings();
		}		
		
		$this->tpl->setContent($a_form->getHTML());
	}

	protected function saveSettings()
	{
		global $ilCtrl;
		
		$this->checkPermission("write");
		
		$form = $this->initFormSettings();
		if($form->checkInput())
		{			
			$obi = (bool)$form->getInput("act")
				? (bool)$form->getInput("obi")
				: null;
		
			$handler = ilBadgeHandler::getInstance();
			$handler->setActive((bool)$form->getInput("act"));
			$handler->setObiActive($obi);
			$handler->setObiOrganisation(trim($form->getInput("obi_org")));
			$handler->setObiContact(trim($form->getInput("obi_cont")));
			$handler->setObiSalt(trim($form->getInput("obi_salt")));
			
			ilUtil::sendSuccess($this->lng->txt("settings_saved"),true);
			$ilCtrl->redirect($this, "editSettings");
		}
		
		$form->setValuesByPost();
		$this->editSettings($form);
	}

	protected function initFormSettings()
	{
	    global $ilAccess;
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt("badge_settings"));
		
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$form->addCommandButton("saveSettings", $this->lng->txt("save"));
			$form->addCommandButton("editSettings", $this->lng->txt("cancel"));
		}

		$act = new ilCheckboxInputGUI($this->lng->txt("badge_service_activate"), "act");
		$act->setInfo($this->lng->txt("badge_service_activate_info"));
		$form->addItem($act);				
		
		$obi = new ilCheckboxInputGUI($this->lng->txt("badge_obi_activate"), "obi");		
		$obi->setInfo($this->lng->txt("badge_obi_activate_info"));
		$form->addItem($obi);
		
			$obi_org = new ilTextInputGUI($this->lng->txt("badge_obi_organisation"), "obi_org");
			$obi_org->setRequired(true);
			$obi_org->setInfo($this->lng->txt("badge_obi_organisation_info"));
			$obi->addSubItem($obi_org);
			
			$obi_contact = new ilEmailInputGUI($this->lng->txt("badge_obi_contact"), "obi_cont");
			$obi_contact->setRequired(true);
			$obi_contact->setInfo($this->lng->txt("badge_obi_contact_info"));
			$obi->addSubItem($obi_contact);
			
			$obi_salt = new ilTextInputGUI($this->lng->txt("badge_obi_salt"), "obi_salt");
			$obi_salt->setRequired(true);
			$obi_salt->setInfo($this->lng->txt("badge_obi_salt_info"));
			$obi->addSubItem($obi_salt);
				
		$handler = ilBadgeHandler::getInstance();
		$act->setChecked($handler->isActive());				
		$obi->setChecked($handler->isObiActive());				
		$obi_org->setValue($handler->getObiOrganistation());				
		$obi_contact->setValue($handler->getObiContact());				
		$obi_salt->setValue($handler->getObiSalt());				
		
		return $form;
	}
	
	
	//
	// types
	//
	
	protected function listTypes()
	{
		global $ilAccess;
		
		$this->assertActive();
		$this->tabs_gui->setTabActive("types");	
		
		include_once "Services/Badge/classes/class.ilBadgeTypesTableGUI.php";
		$tbl = new ilBadgeTypesTableGUI($this, "listTypes",
			$ilAccess->checkAccess("write", "", $this->object->getRefId()));
		$this->tpl->setContent($tbl->getHTML());
	}
	
	protected function saveTypes()
	{	
		$this->assertActive();
		if($this->checkPermissionBool("write"))
		{
			$badges = (array)$_POST["badge"];
			$status = (array)$_POST["badge_active"];		
			$inactive = array_diff($badges, $status);		
			ilBadgeHandler::getInstance()->setInactiveTypes($inactive);					
		}		
		$this->ctrl->redirect($this, "listTypes");
	}
	
	
	//
	// images templates
	//
	
	protected function listImageTemplates()
	{
		global $ilAccess, $lng, $ilToolbar, $ilCtrl;
			
		$this->assertActive();
		$this->tabs_gui->setTabActive("imgtmpl");	
		
		if($this->checkPermissionBool("write"))
		{			
			$ilToolbar->addButton($lng->txt("badge_add_template"), 
				$ilCtrl->getLinkTarget($this, "addImageTemplate"));			
		}
		
		include_once "Services/Badge/classes/class.ilBadgeImageTemplateTableGUI.php";
		$tbl = new ilBadgeImageTemplateTableGUI($this, "listImageTemplates",
			$ilAccess->checkAccess("write", "", $this->object->getRefId()));
		$this->tpl->setContent($tbl->getHTML());
	}		
	
	protected function addImageTemplate(ilPropertyFormGUI $a_form = null)
	{				
		global $tpl;
		
		$this->checkPermission("write");
		
		$this->assertActive();
		$this->tabs_gui->setTabActive("imgtmpl");	
		
		if(!$a_form)
		{
			$a_form = $this->initImageTemplateForm("create");
		}
		
		$tpl->setContent($a_form->getHTML());
	}
	
	protected function initImageTemplateForm($a_mode)
	{
		global $lng, $ilCtrl;
		
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this, "saveBadge"));
		$form->setTitle($lng->txt("badge_image_template_form"));
		
		$title = new ilTextInputGUI($lng->txt("title"), "title");
		$title->setRequired(true);
		$form->addItem($title);		
		
		$img = new ilImageFileInputGUI($lng->txt("image"), "img");
		if($a_mode == "create")
		{
			$img->setRequired(true);
		}
		$img->setALlowDeletion(false);
		$form->addItem($img);
		
		$types = new ilCheckboxGroupInputGUI($lng->txt("badge_types"), "type");		
		$types->setRequired(true);		
		$form->addItem($types);
		
		foreach(ilBadgeHandler::getInstance()->getAvailableTypes() as $id => $type)
		{
			$types->addOption(new ilCheckboxOption($type->getCaption(), $id));
		}
		
		if($a_mode == "create")
		{
			$form->addCommandButton("saveImageTemplate", $lng->txt("save"));
		}
		else
		{
			$form->addCommandButton("updateImageTemplate", $lng->txt("save"));
		}
		$form->addCommandButton("listImageTemplates", $lng->txt("cancel"));
		
		return $form;
	}
	
	protected function saveImageTemplate()
	{
		global $ilCtrl, $lng;
		
		$this->checkPermission("write");
		
		$form = $this->initImageTemplateForm("create");		
		if($form->checkInput())
		{
			include_once "Services/Badge/classes/class.ilBadgeImageTemplate.php";
			$tmpl = new ilBadgeImageTemplate();			
			$tmpl->setTitle($form->getInput("title"));	
			$tmpl->setTypes($form->getInput("type"));
			$tmpl->create();
			
			$tmpl->uploadImage($_FILES["img"]);
			
			ilUtil::sendInfo($lng->txt("settings_saved"), true);
			$ilCtrl->redirect($this, "listImageTemplates");
		}
		
		$form->setValuesByPost();
		$this->addImageTemplate($form);
	}
	
	protected function editImageTemplate(ilPropertyFormGUI $a_form = null)
	{				
		global $ilCtrl, $tpl;
		
		$this->checkPermission("write");
		
		$this->assertActive();
		$this->tabs_gui->setTabActive("imgtmpl");	
				
		$tmpl_id = $_REQUEST["tid"];
		if(!$tmpl_id)
		{
			$ilCtrl->redirect($this, "listImageTemplates");
		}
		
		$ilCtrl->setParameter($this, "tid", $tmpl_id);
		
		include_once "Services/Badge/classes/class.ilBadgeImageTemplate.php";
		$tmpl = new ilBadgeImageTemplate($tmpl_id);			
		
		if(!$a_form)
		{						
			$a_form = $this->initImageTemplateForm("edit");			
			$this->setImageTemplateFormValues($a_form, $tmpl);
		}
		
		$tpl->setContent($a_form->getHTML());
	}
	
	protected function setImageTemplateFormValues(ilPropertyFormGUI $a_form, ilBadgeImageTemplate $a_tmpl)
	{		
		$a_form->getItemByPostVar("title")->setValue($a_tmpl->getTitle());		
		$a_form->getItemByPostVar("img")->setImage($a_tmpl->getImagePath());
		$a_form->getItemByPostVar("img")->setValue($a_tmpl->getImage());
		$a_form->getItemByPostVar("type")->setValue($a_tmpl->getTypes());
	}
	
	protected function updateImageTemplate()
	{
		global $ilCtrl, $lng;
		
		$this->checkPermission("write");
		
		$tmpl_id = $_REQUEST["tid"];
		if(!$tmpl_id)
		{
			$ilCtrl->redirect($this, "listImageTemplates");
		}
		
		$ilCtrl->setParameter($this, "tid", $tmpl_id);
		
		$form = $this->initImageTemplateForm("update");		
		if($form->checkInput())
		{						
			include_once "Services/Badge/classes/class.ilBadgeImageTemplate.php";
			$tmpl = new ilBadgeImageTemplate($tmpl_id);							
			$tmpl->setTitle($form->getInput("title"));
			$tmpl->setTypes($form->getInput("type"));			
			$tmpl->update();
			
			$tmpl->uploadImage($_FILES["img"]);			
		
			ilUtil::sendInfo($lng->txt("settings_saved"), true);
			$ilCtrl->redirect($this, "listImageTemplates");
		}
		
		$form->setValuesByPost();
		$this->editImageTemplate($form);
	}
}