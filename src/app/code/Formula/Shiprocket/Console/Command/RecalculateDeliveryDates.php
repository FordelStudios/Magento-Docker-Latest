<?php
namespace Formula\Shiprocket\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Formula\Shiprocket\Plugin\OrderSavePlugin;

class RecalculateDeliveryDates extends Command
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var OrderSavePlugin
     */
    private $orderSavePlugin;

    /**
     * Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     * @param OrderSavePlugin $orderSavePlugin
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState,
        OrderSavePlugin $orderSavePlugin
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->orderSavePlugin = $orderSavePlugin;
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('formula:shiprocket:recalculate-delivery-dates')
            ->setDescription('Recalculate delivery dates for existing orders')
            ->addArgument('customer_id', InputArgument::OPTIONAL, 'Customer ID to recalculate for', null);
        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area code already set
        }

        $customerId = $input->getArgument('customer_id');
        
        $output->writeln('<info>Recalculating delivery dates for orders...</info>');

        // Build search criteria
        $searchCriteriaBuilder = $this->searchCriteriaBuilder
            ->addFilter('status', ['pending', 'processing'], 'in')
            ->setPageSize(100);

        if ($customerId) {
            $searchCriteriaBuilder->addFilter('customer_id', $customerId);
            $output->writeln('<info>Filtering by customer ID: ' . $customerId . '</info>');
        }

        $searchCriteria = $searchCriteriaBuilder->create();
        $orders = $this->orderRepository->getList($searchCriteria);

        $output->writeln('<info>Found ' . $orders->getTotalCount() . ' orders to process</info>');

        $processed = 0;
        $skipped = 0;

        foreach ($orders->getItems() as $order) {
            try {
                // Clear existing delivery date to force recalculation
                $order->setData('est_delivery_date', null);

                // Use the plugin to calculate delivery date
                $this->orderSavePlugin->beforeSave($this->orderRepository, $order);

                // Save the order if delivery date was calculated
                if ($order->getData('est_delivery_date')) {
                    $this->orderRepository->save($order);
                    $output->writeln('✓ Processed Order #' . $order->getIncrementId() . ' - Delivery Date: ' . $order->getData('est_delivery_date'));
                    $processed++;
                } else {
                    $output->writeln('⚠ Skipped Order #' . $order->getIncrementId() . ' - No delivery date calculated');
                    $skipped++;
                }
            } catch (\Exception $e) {
                $output->writeln('<error>✗ Failed to process Order #' . $order->getIncrementId() . ': ' . $e->getMessage() . '</error>');
                $skipped++;
            }
        }

        $output->writeln('---');
        $output->writeln('<info>Processing complete:</info>');
        $output->writeln('<info>Processed: ' . $processed . ' orders</info>');
        $output->writeln('<comment>Skipped: ' . $skipped . ' orders</comment>');

        return Command::SUCCESS;
    }
}