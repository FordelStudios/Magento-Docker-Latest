<?php
namespace Formula\Shiprocket\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;

class TestOrderDeliveryDate extends Command
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
     * Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('formula:shiprocket:test-delivery-date')
            ->setDescription('Test order delivery date estimation')
            ->addArgument('customer_id', InputArgument::OPTIONAL, 'Customer ID to test with', 161);
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
        
        $output->writeln('<info>Testing order delivery date estimation for customer ID: ' . $customerId . '</info>');

        // Search for orders with pending or processing status for the customer
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->addFilter('status', ['pending', 'processing'], 'in')
            ->setPageSize(5)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria);

        $output->writeln('<info>Found ' . $orders->getTotalCount() . ' orders</info>');

        foreach ($orders->getItems() as $order) {
            $output->writeln('---');
            $output->writeln('Order #' . $order->getIncrementId());
            $output->writeln('Status: ' . $order->getStatus());
            $output->writeln('Weight: ' . ($order->getWeight() ?: 'N/A'));
            
            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress) {
                $output->writeln('Shipping Postcode: ' . $shippingAddress->getPostcode());
            }

            $payment = $order->getPayment();
            if ($payment) {
                $output->writeln('Payment Method: ' . $payment->getMethod());
            }

            // Check if estimated delivery date was added
            $extensionAttributes = $order->getExtensionAttributes();
            if ($extensionAttributes && $extensionAttributes->getEstDeliveryDate()) {
                $output->writeln('<info>Estimated Delivery Date: ' . $extensionAttributes->getEstDeliveryDate() . '</info>');
            } else {
                $output->writeln('<error>No estimated delivery date found</error>');
            }
        }

        return Command::SUCCESS;
    }
}