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
 * @author jposselt@databay.de
 */
class ilChatroomObjectDefinition
{
    /**
     * Module base path, set to "components/ILIAS/$this->moduleName/"
     * @var string
     */
    private readonly string $moduleBasePath;

    /**
     * always set to 'classes'
     * @var string
     */
    private readonly string $relativeClassPath;

    /**
     * GUIScope
     * set to '' for single instance or 'admin' for general administration
     * @var string
     */
    private readonly string $guiScope;

    public function __construct(
        /**
         * Module name, defaults to 'Chatroom'
         */
        private readonly string $moduleName,
        string $moduleBasePath,
        string $relativeClassPath = 'classes',
        string $guiScope = ''
    ) {
        $this->moduleBasePath = rtrim($moduleBasePath, '/\\');
        $this->relativeClassPath = rtrim($relativeClassPath);
        $this->guiScope = rtrim($guiScope);
    }

    /**
     * Returns an Instance of ilChatroomObjectDefinition, using given $moduleName
     * as parameter.
     */
    public static function getDefaultDefinition(string $moduleName): self
    {
        return new self($moduleName, '../components/ILIAS/' . $moduleName . '/');
    }

    /**
     * Returns an Instance of ilChatroomObjectDefinition, using given $moduleName
     * and $guiScope as parameters.
     * @param string $guiScope Optional. 'admin' or ''. Default ''
     */
    public static function getDefaultDefinitionWithCustomGUIPath(string $moduleName, string $guiScope = ''): self
    {
        return new self(
            $moduleName,
            '../components/ILIAS/' . $moduleName . '/',
            'classes',
            $guiScope
        );
    }

    public function hasGUI(string $gui): bool
    {
        $path = $this->getGUIPath($gui);

        return class_exists($this->getGUIClassName($gui)) && file_exists($path);
    }

    /**
     * Builds gui path using given $gui and returns it.
     */
    public function getGUIPath(string $gui): string
    {
        return (
            $this->moduleBasePath . '/' .
            $this->relativeClassPath . '/' .
            $this->guiScope . 'gui/class.' . $this->getGUIClassName($gui) . '.php'
        );
    }

    /**
     * Builds gui classname using given $gui and returns it.
     */
    public function getGUIClassName(string $gui): string
    {
        return 'il' . $this->moduleName . ucfirst($this->guiScope) . ucfirst($gui) . 'GUI';
    }

    /**
     * Builds and returns new gui using given $gui and $gui
     */
    public function buildGUI(string $gui, ilChatroomObjectGUI $chatroomObjectGUI): ilChatroomGUIHandler
    {
        $className = $this->getGUIClassName($gui);
        return new $className($chatroomObjectGUI);
    }
}
