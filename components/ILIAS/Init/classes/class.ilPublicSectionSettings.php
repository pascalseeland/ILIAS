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

/**
 * Description of class class
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilPublicSectionSettings
{
    /**
     * @var ilPublicSectionSettings
     */
    protected static $instance = null;

    private ilSetting $settings;

    private bool $enabled = false;

    /**
     * @var string[]
     */
    private array $domains = array();

    /**
     * read settings
     */
    private function __construct()
    {
        global $DIC;

        $this->settings = $DIC->settings();
        $this->read();
    }

    public static function getInstance(): ilPublicSectionSettings
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string[] $domains
     */
    public function setDomains(array $domains): void
    {
        $this->domains = $domains;
    }

    /**
     *
     * @return string[]
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isEnabledForDomain(string $a_domain): bool
    {
        if (!$this->enabled) {
            return false;
        }
        if (count($this->domains)) {
            if (in_array(trim($a_domain), $this->getDomains())) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    public function setEnabled(bool $stat): void
    {
        $this->enabled = $stat;
    }

    public function save(): void
    {
        $this->settings->set('pub_section', (string) $this->isEnabled());
        $this->settings->set('pub_section_domains', serialize($this->getDomains()));
    }

    /**
     * read settings
     */
    protected function read(): void
    {
        $this->enabled = (bool) $this->settings->get('pub_section', (string) $this->enabled);
        $domains = $this->settings->get('pub_section_domains', serialize($this->domains));
        $this->domains = (array) unserialize($domains);
    }
}
