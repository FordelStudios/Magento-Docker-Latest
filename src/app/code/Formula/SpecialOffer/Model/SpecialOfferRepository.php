<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Model;

use Formula\SpecialOffer\Api\Data\SpecialOfferInterface;
use Formula\SpecialOffer\Api\SpecialOfferRepositoryInterface;
use Formula\SpecialOffer\Model\ResourceModel\SpecialOffer as ResourceModel;
use Formula\SpecialOffer\Model\ResourceModel\SpecialOffer\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class SpecialOfferRepository implements SpecialOfferRepositoryInterface
{
    public function __construct(
        private readonly ResourceModel $resourceModel,
        private readonly SpecialOfferFactory $specialOfferFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getById(int $entityId): SpecialOfferInterface
    {
        $specialOffer = $this->specialOfferFactory->create();
        $this->resourceModel->load($specialOffer, $entityId);

        if (!$specialOffer->getEntityId()) {
            throw new NoSuchEntityException(
                __('Special Offer with ID "%1" does not exist.', $entityId)
            );
        }

        return $specialOffer;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): array
    {
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter(
                    $filter->getField(),
                    [$condition => $filter->getValue()]
                );
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getActiveOffers(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);

        $now = $this->dateTime->gmtDate();

        // Start date: NULL or <= now
        $collection->addFieldToFilter(
            ['start_date', 'start_date'],
            [['null' => true], ['lteq' => $now]]
        );

        // End date: NULL or >= now
        $collection->addFieldToFilter(
            ['end_date', 'end_date'],
            [['null' => true], ['gteq' => $now]]
        );

        $collection->setOrder('sort_order', 'ASC');

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function save(SpecialOfferInterface $specialOffer): SpecialOfferInterface
    {
        try {
            $this->resourceModel->save($specialOffer);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save the special offer: %1', $e->getMessage()),
                $e
            );
        }

        return $specialOffer;
    }

    /**
     * @inheritDoc
     */
    public function delete(SpecialOfferInterface $specialOffer): bool
    {
        try {
            $this->resourceModel->delete($specialOffer);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete the special offer: %1', $e->getMessage()),
                $e
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }
}
