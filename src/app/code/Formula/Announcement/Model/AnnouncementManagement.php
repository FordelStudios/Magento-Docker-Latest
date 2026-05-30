<?php
declare(strict_types=1);

namespace Formula\Announcement\Model;

use Formula\Announcement\Api\AnnouncementManagementInterface;
use Formula\Announcement\Api\Data\AnnouncementInterface;
use Formula\Announcement\Api\Data\AnnouncementInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class AnnouncementManagement implements AnnouncementManagementInterface
{
    private const XML_PATH_ENABLED = 'formula_announcement/general/enabled';
    private const XML_PATH_TEXT = 'formula_announcement/general/text';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AnnouncementInterfaceFactory
     */
    private $announcementFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AnnouncementInterfaceFactory $announcementFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->announcementFactory = $announcementFactory;
    }

    public function getAnnouncement(): AnnouncementInterface
    {
        $enabled = $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        $text = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TEXT,
            ScopeInterface::SCOPE_STORE
        );

        /** @var AnnouncementInterface $announcement */
        $announcement = $this->announcementFactory->create();
        $announcement->setEnabled($enabled);
        $announcement->setText($text);

        return $announcement;
    }
}
