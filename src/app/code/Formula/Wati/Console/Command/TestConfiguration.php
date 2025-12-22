<?php
declare(strict_types=1);

namespace Formula\Wati\Console\Command;

use Formula\Wati\Helper\Data as WatiHelper;
use Formula\Wati\Service\WatiApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to test Wati configuration
 */
class TestConfiguration extends Command
{
    /**
     * @var WatiHelper
     */
    protected $watiHelper;

    /**
     * @var WatiApiService
     */
    protected $watiApiService;

    /**
     * @param WatiHelper $watiHelper
     * @param WatiApiService $watiApiService
     * @param string|null $name
     */
    public function __construct(
        WatiHelper $watiHelper,
        WatiApiService $watiApiService,
        string $name = null
    ) {
        $this->watiHelper = $watiHelper;
        $this->watiApiService = $watiApiService;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('wati:test-config')
            ->setDescription('Test Wati WhatsApp integration configuration');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Testing Wati Configuration...</info>');
        $output->writeln('');

        // Check if module is enabled
        if (!$this->watiHelper->isEnabled()) {
            $output->writeln('<error>Wati integration is DISABLED</error>');
            $output->writeln('Enable it in: Admin > Stores > Configuration > Formula > Wati WhatsApp');
            return Command::FAILURE;
        }
        $output->writeln('<info>✓ Module is ENABLED</info>');

        // Check API endpoint
        $endpoint = $this->watiHelper->getApiEndpoint();
        if (empty($endpoint)) {
            $output->writeln('<error>✗ API endpoint not configured</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>✓ API Endpoint:</info> ' . $endpoint);

        // Check API token
        $token = $this->watiHelper->getApiToken();
        if (empty($token)) {
            $output->writeln('<error>✗ API token not configured</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>✓ API Token:</info> ' . substr($token, 0, 20) . '...');

        // Check webhook secret
        $webhookSecret = $this->watiHelper->getWebhookSecret();
        if (empty($webhookSecret)) {
            $output->writeln('<comment>⚠ Webhook secret not configured (optional but recommended)</comment>');
        } else {
            $output->writeln('<info>✓ Webhook Secret:</info> configured');
        }

        // Check debug mode
        $debugMode = $this->watiHelper->isDebugMode();
        $output->writeln('<info>✓ Debug Mode:</info> ' . ($debugMode ? 'ENABLED' : 'disabled'));

        // Check templates
        $output->writeln('');
        $output->writeln('<info>Configured Templates:</info>');

        $statuses = [
            'pending' => 'Order Placed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'canceled' => 'Cancelled',
            'closed' => 'Refunded'
        ];

        $allTemplatesConfigured = true;
        foreach ($statuses as $status => $label) {
            $template = $this->watiHelper->getTemplateForStatus($status);
            if (empty($template)) {
                $output->writeln("  <comment>⚠ {$label}:</comment> not configured");
                $allTemplatesConfigured = false;
            } else {
                $output->writeln("  <info>✓ {$label}:</info> {$template}");
            }
        }

        // Summary
        $output->writeln('');
        if ($allTemplatesConfigured) {
            $output->writeln('<info>✓ All configuration checks passed!</info>');
        } else {
            $output->writeln('<comment>Configuration is valid but some templates are not configured.</comment>');
            $output->writeln('<comment>Messages will only be sent for statuses with configured templates.</comment>');
        }

        $output->writeln('');
        $output->writeln('To send a test message, use:');
        $output->writeln('  <comment>bin/magento wati:send-test --order-id=123 --status=pending</comment>');

        return Command::SUCCESS;
    }
}
