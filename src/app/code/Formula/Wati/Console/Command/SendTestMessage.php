<?php
declare(strict_types=1);

namespace Formula\Wati\Console\Command;

use Formula\Wati\Service\WatiApiService;
use Formula\Wati\Helper\Data as WatiHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to send a test WhatsApp message
 */
class SendTestMessage extends Command
{
    /**
     * @var WatiApiService
     */
    protected $watiApiService;

    /**
     * @var WatiHelper
     */
    protected $watiHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param WatiApiService $watiApiService
     * @param WatiHelper $watiHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param string|null $name
     */
    public function __construct(
        WatiApiService $watiApiService,
        WatiHelper $watiHelper,
        OrderRepositoryInterface $orderRepository,
        string $name = null
    ) {
        $this->watiApiService = $watiApiService;
        $this->watiHelper = $watiHelper;
        $this->orderRepository = $orderRepository;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('wati:send-test')
            ->setDescription('Send a test WhatsApp message for an order')
            ->addOption(
                'order-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Order entity ID to send test message for'
            )
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                'Status to simulate (pending, processing, shipped, delivered, canceled, closed)',
                'pending'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderId = $input->getOption('order-id');
        $status = $input->getOption('status');

        if (!$orderId) {
            $output->writeln('<error>Please provide --order-id</error>');
            $output->writeln('');
            $output->writeln('Usage: bin/magento wati:send-test --order-id=123 --status=pending');
            return Command::FAILURE;
        }

        // Check if module is enabled
        if (!$this->watiHelper->isEnabled()) {
            $output->writeln('<error>Wati integration is disabled. Enable it in admin configuration.</error>');
            return Command::FAILURE;
        }

        // Check if template exists for this status
        $template = $this->watiHelper->getTemplateForStatus($status);
        if (!$template) {
            $output->writeln("<error>No template configured for status: {$status}</error>");
            $output->writeln('');
            $output->writeln('Available statuses with templates:');

            $statuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'canceled', 'closed'];
            foreach ($statuses as $s) {
                $t = $this->watiHelper->getTemplateForStatus($s);
                if ($t) {
                    $output->writeln("  - {$s}: {$t}");
                }
            }

            return Command::FAILURE;
        }

        try {
            $order = $this->orderRepository->get($orderId);

            $output->writeln('<info>Sending test message...</info>');
            $output->writeln('');
            $output->writeln('Order ID: ' . $order->getIncrementId());
            $output->writeln('Status: ' . $status);
            $output->writeln('Template: ' . $template);

            // Get phone number
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress) {
                $shippingAddress = $order->getBillingAddress();
            }

            if ($shippingAddress && $shippingAddress->getTelephone()) {
                $phone = $this->watiHelper->formatPhoneForWhatsApp($shippingAddress->getTelephone());
                $output->writeln('Phone: ' . $phone);
            } else {
                $output->writeln('<error>No phone number found for this order</error>');
                return Command::FAILURE;
            }

            $output->writeln('');

            $result = $this->watiApiService->sendOrderStatusNotification($order, $status);

            if ($result['success']) {
                $output->writeln('<info>✓ Message sent successfully!</info>');
                if (!empty($result['message_id'])) {
                    $output->writeln('Message ID: ' . $result['message_id']);
                }
            } else {
                $output->writeln('<error>✗ Failed to send message</error>');
                $output->writeln('Error: ' . ($result['error'] ?? 'Unknown error'));

                if (!empty($result['response'])) {
                    $output->writeln('');
                    $output->writeln('API Response:');
                    $output->writeln(json_encode($result['response'], JSON_PRETTY_PRINT));
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (NoSuchEntityException $e) {
            $output->writeln("<error>Order with ID {$orderId} not found</error>");
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
