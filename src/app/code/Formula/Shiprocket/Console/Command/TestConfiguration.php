<?php
namespace Formula\Shiprocket\Console\Command;

use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class TestConfiguration extends Command
{
    const PINCODE = 'pincode';
    const WEIGHT = 'weight';
    const COD = 'cod';

    /**
     * @var ShiprocketHelper
     */
    private $shiprocketHelper;

    /**
     * @param ShiprocketHelper $shiprocketHelper
     * @param string|null $name
     */
    public function __construct(
        ShiprocketHelper $shiprocketHelper,
        $name = null
    ) {
        $this->shiprocketHelper = $shiprocketHelper;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('shiprocket:test-configuration')
            ->setDescription('Test Shiprocket configuration and API connectivity')
            ->addOption(
                self::PINCODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Delivery pincode to test'
            )
            ->addOption(
                self::WEIGHT,
                null,
                InputOption::VALUE_REQUIRED,
                'Package weight in kg',
                '1'
            )
            ->addOption(
                self::COD,
                null,
                InputOption::VALUE_OPTIONAL,
                'Cash on delivery (1 for yes, 0 for no)',
                '0'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Testing Shiprocket Configuration...</info>');

        // Check if module is enabled
        if (!$this->shiprocketHelper->isEnabled()) {
            $output->writeln('<error>❌ Shiprocket integration is disabled</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln('<info>✅ Module is enabled</info>');

        // Check configuration
        $email = $this->shiprocketHelper->getEmail();
        $password = $this->shiprocketHelper->getPassword();
        $pickupPostcode = $this->shiprocketHelper->getPickupPostcode();

        if (empty($email)) {
            $output->writeln('<error>❌ Email is not configured</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if (empty($password)) {
            $output->writeln('<error>❌ Password is not configured</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if (empty($pickupPostcode)) {
            $output->writeln('<error>❌ Pickup postcode is not configured</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln('<info>✅ Configuration is complete</info>');
        $output->writeln("Email: {$email}");
        $output->writeln("Pickup Postcode: {$pickupPostcode}");

        // Test API if pincode is provided
        $pincode = $input->getOption(self::PINCODE);
        if ($pincode) {
            $output->writeln('<info>Testing API with pincode: ' . $pincode . '</info>');
            
            $weight = (float) $input->getOption(self::WEIGHT);
            $cod = (bool) $input->getOption(self::COD);

            try {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $serviceability = $objectManager->create(\Formula\Shiprocket\Model\Serviceability::class);
                
                $result = $serviceability->checkServiceability($pincode, $cod, $weight);
                
                if ($result['success']) {
                    $output->writeln('<info>✅ API test successful</info>');
                    $output->writeln('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
                } else {
                    $output->writeln('<error>❌ API test failed: ' . $result['error'] . '</error>');
                    return \Magento\Framework\Console\Cli::RETURN_FAILURE;
                }
            } catch (\Exception $e) {
                $output->writeln('<error>❌ API test exception: ' . $e->getMessage() . '</error>');
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }
        } else {
            $output->writeln('<info>⚠️  No pincode provided. Skipping API test.</info>');
            $output->writeln('Use --pincode option to test API connectivity');
        }

        $output->writeln('<info>🎉 Configuration test completed successfully!</info>');
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
} 