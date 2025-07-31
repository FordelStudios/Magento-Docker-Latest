<?php
/**
 * Console Command for testing (Optional)
 * File: src/app/code/Formula/Shiprocket/Console/Command/TestServiceability.php
 */
namespace Formula\Shiprocket\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Formula\Shiprocket\Model\Serviceability;

class TestServiceability extends Command
{
    /**
     * @var Serviceability
     */
    private $serviceability;

    /**
     * Constructor
     *
     * @param Serviceability $serviceability
     */
    public function __construct(Serviceability $serviceability)
    {
        $this->serviceability = $serviceability;
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('formula:shiprocket:test')
             ->setDescription('Test Shiprocket serviceability API')
             ->addArgument('pincode', InputArgument::REQUIRED, 'Delivery pincode')
             ->addArgument('cod', InputArgument::OPTIONAL, 'COD enabled (true/false)', 'false');
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
        $pincode = $input->getArgument('pincode');
        $cod = $input->getArgument('cod') === 'true';

        $output->writeln('<info>Testing Shiprocket API with:</info>');
        $output->writeln('Pincode: ' . $pincode);
        $output->writeln('COD: ' . ($cod ? 'Yes' : 'No'));

        $result = $this->serviceability->checkServiceability($pincode, $cod);

        if ($result['success']) {
            $output->writeln('<info>Success!</info>');
            $output->writeln(json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $output->writeln('<error>Error: ' . $result['error'] . '</error>');
        }

        return 0;
    }
}